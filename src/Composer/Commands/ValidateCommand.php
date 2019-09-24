<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

use Vaimo\ComposerPatches\Composer\ConfigKeys;
use Vaimo\ComposerPatches\Config;
use Vaimo\ComposerPatches\Patch\Definition as Patch;
use Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool;
use Vaimo\ComposerPatches\Composer\Context as ComposerContext;
use Vaimo\ComposerPatches\Utils\PathUtils;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateCommand extends \Composer\Command\BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('patch:validate');

        $this->setDescription('Validate that all patches have proper target configuration');

        $this->addOption(
            '--from-source',
            null,
            InputOption::VALUE_NONE,
            'Use latest information from package configurations in vendor folder'
        );

        $this->addOption(
            '--local',
            null,
            InputOption::VALUE_NONE,
            'Only validate patches that are owned by the ROOT package'
        );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Scanning packages for orphan patches</info>');

        $composer = $this->getComposer();
        
        $localOnly = $input->getOption('local');
        
        $patchListAnalyser = new \Vaimo\ComposerPatches\Patch\DefinitionList\Analyser();
        
        $pluginConfig = array(
            Config::PATCHER_SOURCES => $this->createSourcesEnablerConfig($localOnly)
        );

        $contextFactory = new \Vaimo\ComposerPatches\Factories\ComposerContextFactory($composer);
        $composerContext = $contextFactory->create();

        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composerContext, array(
            Config::PATCHER_FROM_SOURCE => (bool)$input->getOption('from-source')
        ));
        
        $repository = $composer->getRepositoryManager()->getLocalRepository();

        $pluginConfig = $configFactory->create(array($pluginConfig));
        
        $patchesLoader = $this->createPatchesLoader($composerContext, $pluginConfig);

        $patches = $patchesLoader->loadFromPackagesRepository($repository);
        
        $patchPaths = $patchListAnalyser->extractValue($patches, array(Patch::PATH, Patch::SOURCE));
        
        $patchDefines = array_combine(
            $patchPaths,
            $patchListAnalyser->extractDictionary($patches, array(Patch::OWNER, Patch::URL))
        );
        
        $patchStatuses = array_filter(
            array_combine(
                $patchPaths,
                $patchListAnalyser->extractValue($patches, array(Patch::STATUS_LABEL))
            ) ?: array()
        );
        
        $matches = $this->resolveValidationTargets($repository, $pluginConfig);

        $installPaths = $this->collectInstallPaths($matches);
        
        $fileMatches = $this->collectPatchFilesFromPackages($matches, $pluginConfig);
        
        $groups = $this->collectOrphans($fileMatches, $patchDefines, $installPaths, $patchStatuses);
        
        $this->outputOrphans($output, $groups);

        $output->writeln(
            $groups ? '<error>Orphans found!</error>' : '<info>Validation completed successfully</info>'
        );
        
        return (int)(bool)$groups;
    }

    private function createSourcesEnablerConfig($localOnly)
    {
        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();

        $defaultValues = $configDefaults->getPatcherConfig();
        
        if (isset($defaultValues[Config::PATCHER_SOURCES]) && is_array($defaultValues[Config::PATCHER_SOURCES])) {
            $sourceKeys = array_keys((array)$defaultValues[Config::PATCHER_SOURCES]);

            return $localOnly
                ? array_replace(array_fill_keys($sourceKeys, false), array('project' => true))
                : array_fill_keys($sourceKeys, true);
        }

        return array();
    }
    
    private function collectInstallPaths(array $matches)
    {
        $composer = $this->getComposer();
        $projectRoot = getcwd();

        $installationManager = $composer->getInstallationManager();
        
        $installPaths = array();
        foreach ($matches as $packageName => $package) {
            $installPaths[$packageName] = $package instanceof \Composer\Package\RootPackage
                ? $projectRoot
                : $installationManager->getInstallPath($package);
        }
        
        return $installPaths;
    }

    private function collectPatchFilesFromPackages(array $matches, Config $pluginConfig)
    {
        $composer = $this->getComposer();
        $composerConfig = $composer->getConfig();

        $configReaderFactory = new \Vaimo\ComposerPatches\Factories\PatcherConfigReaderFactory($composer);
        $dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();
        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $fileSystemUtils = new \Vaimo\ComposerPatches\Utils\FileSystemUtils();

        $projectRoot = getcwd();

        $vendorRoot = $composerConfig->get(ConfigKeys::VENDOR_DIR);
        $vendorPath = ltrim(
            substr($vendorRoot, strlen($projectRoot)),
            DIRECTORY_SEPARATOR
        );
        
        $defaultIgnores = array($vendorPath, '.hg', '.git', '.idea');

        $patcherConfigReader = $configReaderFactory->create($pluginConfig);
        
        $installPaths = $this->collectInstallPaths($matches);

        $fileMatchGroups = array();

        foreach ($matches as $packageName => $package) {
            $patcherConfig = $patcherConfigReader->readFromPackage($package);

            $ignores = $dataUtils->getValueByPath(
                $patcherConfig,
                array(Config::PATCHER_CONFIG_ROOT, Config::PATCHES_IGNORE),
                array()
            );

            $installPath = $installPaths[$packageName];

            $skippedPaths = $dataUtils->prefixArrayValues(
                array_merge($defaultIgnores, $ignores),
                $installPath . DIRECTORY_SEPARATOR
            );

            $filter = $filterUtils->composeRegex(
                $filterUtils->invertRules($skippedPaths),
                '/'
            );

            $filter = sprintf('%s.+\.patch/i', rtrim($filter, '/'));

            $searchResult = $fileSystemUtils->collectPathsRecursively($installPath, $filter);

            $fileMatchGroups[] = array_fill_keys($searchResult, array(
                Patch::OWNER => $packageName,
                Patch::URL => ''
            ));
        }

        return array_reduce($fileMatchGroups, 'array_replace', array());
    }
    
    private function resolveValidationTargets(PackageRepository $repository, Config $pluginConfig)
    {
        $composer = $this->getComposer();

        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($composer->getPackage())
        );
        
        $srcResolverFactory = new \Vaimo\ComposerPatches\Factories\SourcesResolverFactory($composer);
        $packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();

        $srcResolver = $srcResolverFactory->create($pluginConfig);
        
        $sources = $srcResolver->resolvePackages($repository);

        $repositoryUtils = new \Vaimo\ComposerPatches\Utils\RepositoryUtils();

        $pluginPackage = $packageResolver->resolveForNamespace(
            $repository->getCanonicalPackages(),
            __NAMESPACE__
        );

        $pluginName = $pluginPackage->getName();

        $pluginUsers = array_merge(
            $repositoryUtils->filterByDependency($repository, $pluginName),
            array($composer->getPackage())
        );

        return array_intersect_key(
            $packageListUtils->listToNameDictionary($sources),
            $packageListUtils->listToNameDictionary($pluginUsers)
        );
    }
    
    private function createPatchesLoader(ComposerContext $composerContext, Config $pluginConfig)
    {
        $composer = $this->getComposer();
        
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);

        $componentOverrides = array(
            'constraints' => false,
            'platform' => false,
            'targets-resolver' => false,
            'local-exclude' => false,
            'root-patch' => false,
            'global-exclude' => false
        );
        
        $loaderComponentsPool = $this->createLoaderPool($composerContext, $componentOverrides);

        return $loaderFactory->create($loaderComponentsPool, $pluginConfig, true);
    }

    private function createLoaderPool(ComposerContext $composerContext, array $componentUpdates = array())
    {
        $appIO = $this->getIO();

        $componentPool = new ComponentPool($composerContext, $appIO, true);

        foreach ($componentUpdates as $componentName => $replacement) {
            $componentPool->registerComponent($componentName, $replacement);
        }

        return $componentPool;
    }
    
    private function collectOrphans(array $files, array $patches, array $paths, array $statuses)
    {
        $orphanFiles = array_diff_key($files, $patches);
        $orphanConfig = array_diff_key($patches, $files);

        /**
         * Make sure that downloaded patches are not perceived as missing files
         */
        $orphanConfig = array_diff_key(
            $orphanConfig,
            array_flip(
                array_filter(array_keys($orphanConfig), 'file_exists')
            )
        );

        $groups = array_fill_keys(array_keys($paths), array());
        
        foreach ($orphanFiles as $path => $config) {
            $ownerName = $config[Patch::OWNER];
            $installPath = $paths[$ownerName];
            
            $groups[$ownerName][] = array(
                'issue' => 'NO CONFIG',
                'path' => $config[Patch::URL] ?: PathUtils::reducePathLeft($path, $installPath)
            );
        }
        
        foreach ($orphanConfig as $path => $config) {
            $ownerName = $config[Patch::OWNER];
            $installPath = $paths[$ownerName];
            
            $groups[$ownerName][] = array(
                'issue' => isset($statuses[$path]) && $statuses[$path]
                    ? $statuses[$path]
                    : 'NO FILE',
                'path' => $config[Patch::URL] ?: PathUtils::reducePathLeft($path, $installPath)
            );
        }

        return array_filter($groups);
    }

    private function outputOrphans(OutputInterface $output, array $groups)
    {
        $lines = array();
        
        foreach ($groups as $packageName => $items) {
            $lines[] = sprintf('  - <info>%s</info>', $packageName);

            foreach ($items as $item) {
                $lines[] = sprintf(
                    '    ~ %s [<fg=red>%s</>]',
                    $item['path'],
                    $item['issue']
                );
            }
        }
        
        foreach ($lines as $line) {
            $output->writeln($line);
        }
    }
}
