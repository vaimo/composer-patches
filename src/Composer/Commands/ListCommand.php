<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

use Symfony\Component\Console\Input\InputOption;
use Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;
use Vaimo\ComposerPatches\Config;
use Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

class ListCommand extends \Composer\Command\BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('patch:list');

        $this->setDescription('List all registered and eligible (based on project config) patches');

        $this->addArgument(
            'targets',
            \Symfony\Component\Console\Input\InputArgument::IS_ARRAY,
            'Packages for the patcher to target',
            array()
        );

        $this->addOption(
            '--no-dev',
            null,
            InputOption::VALUE_NONE,
            'Disables installation of require-dev packages'
        );

        $this->addOption(
            '--filter',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Apply only those patch files/sources that match with provided filter'
        );

        $this->addOption(
            '--excluded',
            null,
            InputOption::VALUE_NONE,
            'Include patches that have been ruled out based on some constraint mismatch'
        );

        $this->addOption(
            '--with-excludes',
            null,
            InputOption::VALUE_NONE,
            'Alias for \'excluded\' argument'
        );
        
        $this->addOption(
            '--brief',
            null,
            InputOption::VALUE_NONE,
            'Show more compact output of the list (remove description, owner , etc)'
        );
        
        $this->addOption(
            '--status',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Match specific statuses (changed, new, applied, removed)'
        );

        $this->addOption(
            '--from-source',
            null,
            InputOption::VALUE_NONE,
            'Apply patches based on information directly from packages in vendor folder'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();

        $isDevMode = !$input->getOption('no-dev');
        $withExcluded = $input->getOption('excluded') || $input->getOption('with-excludes');
        $beBrief = $input->getOption('brief');

        $filters = array(
            PatchDefinition::SOURCE => $input->getOption('filter'),
            PatchDefinition::TARGETS => $input->getArgument('targets')
        );

        $statusFilters = array_map('strtolower', $input->getOption('status'));

        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();

        $defaultValues = $configDefaults->getPatcherConfig();
        
        $config = array(
            Config::PATCHER_SOURCES => array_fill_keys(
                array_keys($defaultValues[Config::PATCHER_SOURCES]),
                true
            )
        );
        
        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();

        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer);
        $configInstance = $configFactory->create(array($config));
        
        $filteredPool = $this->createLoaderPool();

        $installationManager = $composer->getInstallationManager();
        $composerConfig = clone $composer->getConfig();

        $vendorRoot = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $installationManager,
            $vendorRoot
        );
        
        $unfilteredPool = $this->createLoaderPool(array(
            'constraints' => false, 
            'local-exclude' => false, 
            'root-patch' => false,
            'global-exclude' => false, 
            'targets-resolver' => new LoaderComponents\TargetsResolverComponent($packageInfoResolver, true)
        ));

        $listResolver = new ListResolvers\FilteredListResolver($filters);

        $repositoryStateAnalyserFactory = new \Vaimo\ComposerPatches\Factories\RepositoryStateAnalyserFactory(
            $composer
        );

        $repositoryStateAnalyser = $repositoryStateAnalyserFactory->create($configInstance);
        
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);
        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector(
            array($composer->getPackage())
        );

        $repositoryAnalyser = new \Vaimo\ComposerPatches\Repository\Analyser(
            $packageCollector,
            $repositoryStateAnalyser,
            $listResolver
        );
        
        $repository = $composer->getRepositoryManager()->getLocalRepository();
        
        $filteredPatchesLoader = $loaderFactory->create($filteredPool, $configInstance, $isDevMode);
        $filteredPatches = $filteredPatchesLoader->loadFromPackagesRepository($repository);
        
        $patches = $repositoryAnalyser->getPatchesWithStatuses(
            $repository,
            $filteredPatches, 
            $statusFilters
        );
        
        $shouldIncludeExcludedPatches = $withExcluded 
            && (!$statusFilters || preg_match($filterUtils->composeRegex($statusFilters, '/'), 'excluded'));
        
        if ($shouldIncludeExcludedPatches) {
            $patchesLoader = $loaderFactory->create($unfilteredPool, $configInstance, $isDevMode);

            $allPatches = $patchesLoader->loadFromPackagesRepository($repository);
            
            $patchesQueue = $listResolver->resolvePatchesQueue($allPatches);
            
            $excludedPatches = $patchListUtils->updateStatuses(
                array_filter($patchListUtils->diffListsByPath($patchesQueue, $filteredPatches)),
                'excluded'
            );

            $patches = array_replace_recursive(
                $patches,
                $patchListUtils->updateStatuses($excludedPatches, 'excluded')
            );

            array_walk($patches, function (array &$group) {
                ksort($group);
            }, $patches);
        }
        
        if ($beBrief) {
            $patches = $patchListUtils->embedInfoToItems($patches, array(
                PatchDefinition::LABEL => false,
                PatchDefinition::OWNER => false
            ));
        }
        
        $this->generateOutput($output, $patches);
    }
    
    private function createLoaderPool(array $componentUpdates = array())
    {
        $composer = $this->getComposer();
        $io = $this->getIO();

        $componentPool = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composer,
            $io
        );

        foreach ($componentUpdates as $componentName => $replacement) {
            $componentPool->registerComponent($componentName, $replacement);
        }
        
        return $componentPool;
    }
    
    private function generateOutput(OutputInterface $output, array $list)
    {
        $statusDecorators = array(
            'new' => '<info>NEW</info>',
            'changed' => '<info>CHANGED</info>',
            'applied' => '<fg=white;options=bold>APPLIED</>',
            'removed' => '<fg=red>REMOVED</>',
            'excluded' => '<fg=black>EXCLUDED</>',
            'unknown' => 'UNKNOWN'
        );
        
        foreach ($list as $packageName => $patches) {
            $output->writeln(sprintf('<info>%s</info>', $packageName));

            foreach ($patches as $path => $info) {
                $status = isset($info[PatchDefinition::STATUS]) 
                    ? $info[PatchDefinition::STATUS] 
                    : 'unknown';

                $statusLabel = sprintf(' [%s]', $statusDecorators[$status]);
                $owner = $info[PatchDefinition::OWNER];
                
                if ($owner === PatchDefinition::OWNER_UNKNOWN) {
                    $patchInfoLabel = sprintf('  ~ %s%s', $path, $statusLabel);
                } else if ($owner) {
                    $patchInfoLabel = sprintf('  ~ <info>%s</info>: %s%s', $owner, $path, $statusLabel);
                } else {
                    $patchInfoLabel = sprintf('%s%s', $path, $statusLabel);
                }

                $output->writeln($patchInfoLabel);

                $descriptionLines = array_filter(
                    explode(PHP_EOL, $info[PatchDefinition::LABEL])
                );
                
                foreach ($descriptionLines as $line) {
                    $output->writeln(sprintf('    <comment>%s</comment>', $line));
                }
            }

            $output->writeln('');
        }
    }
}
