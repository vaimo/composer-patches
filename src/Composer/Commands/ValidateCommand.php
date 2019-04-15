<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Vaimo\ComposerPatches\Composer\ConfigKeys;
use Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;
use Vaimo\ComposerPatches\Patch\Definition as Patch;
use Vaimo\ComposerPatches\Config;

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

        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();

        $defaultValues = $configDefaults->getPatcherConfig();

        $sourceKeys = array_keys($defaultValues[Config::PATCHER_SOURCES]);
        
        $patchSources = $localOnly
            ? array_replace(array_fill_keys($sourceKeys, false), array('project' => true))
            : array_fill_keys($sourceKeys, true);

        $pluginConfig = array(
            Config::PATCHER_SOURCES => $patchSources
        );
        
        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer);
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);

        $repository = $composer->getRepositoryManager()->getLocalRepository();

        $pluginConfig = $configFactory->create(array($pluginConfig));

        $composerConfig = clone $composer->getConfig();
        $downloader = new \Composer\Util\RemoteFilesystem($this->getIO(), $composerConfig);
        
        $loaderComponentsPool = $this->createLoaderPool(array(
            'constraints' => false,
            'platform' => false,
            'targets-resolver' => false,
            'local-exclude' => false,
            'root-patch' => false,
            'global-exclude' => false,
            'downloader' => new LoaderComponents\DownloaderComponent(
                $composer->getPackage(),
                $downloader,
                true
            )
        ));
        
        $patchesLoader = $loaderFactory->create($loaderComponentsPool, $pluginConfig, true);

        $patches = $patchesLoader->loadFromPackagesRepository($repository);

        $patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();

        $patchPaths = $patchListUtils->extractValue($patches, array(Patch::PATH, Patch::SOURCE));
        
        $patchDefines = array_combine(
            $patchPaths,
            $patchListUtils->extractValue($patches, array(Patch::OWNER))
        );
        
        $patchStatuses = array_filter(
            array_combine(
                $patchPaths,
                $patchListUtils->extractValue($patches, array(Patch::STATUS_LABEL))
            )
        );
        
        $sourcesResolverFactory = new \Vaimo\ComposerPatches\Factories\SourcesResolverFactory($composer);
        $packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();

        $sourcesResolver = $sourcesResolverFactory->create($pluginConfig);

        $sources = $sourcesResolver->resolvePackages($repository);

        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($composer->getPackage())
        );
        
        $repositoryUtils = new \Vaimo\ComposerPatches\Utils\RepositoryUtils();

        $pluginPackage = $packageResolver->resolveForNamespace($repository, __NAMESPACE__);
        
        $pluginName = $pluginPackage->getName();

        $pluginUsers = array_merge(
            $repositoryUtils->filterByDependency($repository, $pluginName),
            array($composer->getPackage())
        );

        $matches = array_intersect_key(
            $packageListUtils->listToNameDictionary($sources),
            $packageListUtils->listToNameDictionary($pluginUsers)
        );

        $installationManager = $composer->getInstallationManager();

        $fileSystemUtils = new \Vaimo\ComposerPatches\Utils\FileSystemUtils();

        $projectRoot = getcwd();
        $vendorRoot = $composerConfig->get(ConfigKeys::VENDOR_DIR);
        $vendorPath = ltrim(
            substr($vendorRoot, strlen($projectRoot)),
            DIRECTORY_SEPARATOR
        );

        $fileMatches = array();
        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();

        $installPaths = array();
        foreach ($matches as $packageName => $package) {
            $installPaths[$packageName] = $package instanceof \Composer\Package\RootPackage
                ? $projectRoot
                : $installationManager->getInstallPath($package);
        }

        $dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();

        $defaultIgnores = array($vendorPath, '.hg', '.git', '.idea');

        $configReaderFactory = new \Vaimo\ComposerPatches\Factories\PatcherConfigReaderFactory(
            $this->getComposer()
        );
        
        $patcherConfigReader = $configReaderFactory->create($pluginConfig);
        
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

            $result = $fileSystemUtils->collectPathsRecursively($installPath, $filter);

            $fileMatches = array_replace(
                $fileMatches,
                array_fill_keys($result, $packageName)
            );
        }
        
        $groups = array_filter(
            $this->collectOrphans($fileMatches, $patchDefines, $installPaths, $patchStatuses)
        );
        
        $this->outputOrphans($output, $groups);

        $output->writeln(
            $groups ? '<error>Orphans found!</error>' : '<info>Validation completed successfully</info>'
        );
        
        return (int)(bool)$groups;
    }

    private function createLoaderPool(array $componentUpdates = array())
    {
        $composer = $this->getComposer();
        $appIO = $this->getIO();

        $componentPool = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composer,
            $appIO
        );

        foreach ($componentUpdates as $componentName => $replacement) {
            $componentPool->registerComponent($componentName, $replacement);
        }

        return $componentPool;
    }
    
    private function collectOrphans($fileMatches, $patchesWithTargets, $installPaths, $patchStatuses)
    {
        $orphanFiles = array_diff_key($fileMatches, $patchesWithTargets);
        
        $orphanConfig = array_diff_key($patchesWithTargets, $fileMatches);

        /**
         * Make sure that downloaded patches are not perceived as missing files
         */
        $orphanConfig = array_diff_key(
            $orphanConfig,
            array_flip(
                array_filter(array_keys($orphanConfig), 'file_exists')
            )
        );

        $groups = array_fill_keys(array_keys($installPaths), array());

        foreach ($orphanFiles as $path => $ownerName) {
            $installPath = $installPaths[$ownerName];
            
            $groups[$ownerName][] = array(
                'issue' => 'NO CONFIG',
                'path' => ltrim(
                    substr($path, strlen($installPath)),
                    DIRECTORY_SEPARATOR
                )
            );
        }
        
        foreach ($orphanConfig as $path => $ownerName) {
            $installPath = $installPaths[$ownerName];

            $pathInfo = parse_url($path);
            $pathIncludesScheme = isset($pathInfo['scheme']) && $pathInfo['scheme'];

            $groups[$ownerName][] = array(
                'issue' => isset($patchStatuses[$path]) && $patchStatuses[$path]
                    ? $patchStatuses[$path]
                    : 'NO FILE',
                'path' => !$pathIncludesScheme
                    ? ltrim(substr($path, strlen($installPath)), DIRECTORY_SEPARATOR)
                    : $path
            );
        }

        return $groups;
    }

    private function outputOrphans(OutputInterface $output, array $groups)
    {
        $lines = array();
        
        foreach ($groups as $packageName => $items) {
            $lines[] = sprintf('  - <info>%s</info>', $packageName);

            foreach ($items as $item) {
                $lines[] = sprintf('    ~ %s [<fg=red>%s</>]', $item['path'], $item['issue']);
            }
        }
        
        foreach ($lines as $line) {
            $output->writeln($line);
        }
    }
}
