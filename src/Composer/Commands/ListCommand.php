<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Composer\Composer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

use Vaimo\ComposerPatches\Interfaces\ListResolverInterface as ListResolver;
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
            'Mark certain patches with MATCH in the output list'
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
            '--with-affected',
            null,
            InputOption::VALUE_NONE,
            'Mark patches that would get re-applied when changed/new patches are added (due to package reinstall)'
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
            'Use latest information from package configurations in vendor folder'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();

        $isDevMode = !$input->getOption('no-dev');
        $withExcluded = $input->getOption('excluded') || $input->getOption('with-excludes');
        $withAffected = $input->getOption('with-affected');
        $beBrief = $input->getOption('brief');
        
        $filters = array(
            PatchDefinition::SOURCE => $input->getOption('filter'),
            PatchDefinition::TARGETS => $input->getArgument('targets')
        );

        $statusFilters = array_map('strtolower', $input->getOption('status'));
        
        $pluginConfig = $this->createConfigWithEnabledSources($composer);
        
        
        $filteredPool = $this->createLoaderPool();
        
        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $composer->getInstallationManager(),
            $composer->getConfig()->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR)
        );
        
        $unfilteredPool = $this->createLoaderPool(array(
            'constraints' => false,
            'platform' => false,
            'local-exclude' => false,
            'root-patch' => false,
            'global-exclude' => false,
            'targets-resolver' => new LoaderComponents\TargetsResolverComponent($packageInfoResolver, true)
        ));

        $hasFilers = (bool)array_filter($filters);
        
        $listResolver = new ListResolvers\FilteredListResolver($filters);
        
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);
        
        $repository = $composer->getRepositoryManager()->getLocalRepository();
        
        $filteredLoader = $loaderFactory->create($filteredPool, $pluginConfig, $isDevMode);
        $filteredPatches = $filteredLoader->loadFromPackagesRepository($repository);
        
        $queueGenerator = $this->createQueueGenerator($composer, $pluginConfig, $listResolver);

        $repoStateGenerator = $this->createStateGenerator($composer);
        
        $repositoryState = $repoStateGenerator->generate($repository);
        
        $applyQueue = $queueGenerator->generateApplyQueue($filteredPatches, $repositoryState);
        $removeQueue = $queueGenerator->generateRemovalQueue($applyQueue, $repositoryState);
        $applyQueue = array_map('array_filter', $applyQueue);

        $patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();

        $filteredPatches = $patchListUtils->mergeLists(
            $filteredPatches,
            $removeQueue
        );

        if ($withAffected) {
            $applyQueue = $patchListUtils->embedInfoToItems(
                $applyQueue,
                array(PatchDefinition::STATUS => 'affected'),
                true
            );
        }
        
        $filteredPatches = $patchListUtils->mergeLists(
            $filteredPatches,
            $patchListUtils->intersectListsByName($applyQueue, $filteredPatches)
        );
        
        $filteredPatches = $patchListUtils->embedInfoToItems(
            $filteredPatches,
            array(PatchDefinition::STATUS => 'applied'),
            true
        );
        
        if ($hasFilers) {
            $filteredPatches = $listResolver->resolvePatchesQueue($filteredPatches);
        }

        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();

        if ($statusFilters) {
            $statusFilter = $filterUtils->composeRegex($statusFilters, '/');

            $filteredPatches = $patchListUtils->applyDefinitionFilter(
                $filteredPatches,
                $statusFilter,
                PatchDefinition::STATUS
            );
        }
        
        $patches = array_filter($filteredPatches);
        
        $shouldAddExcludes = $withExcluded
            && (!$statusFilters || preg_match($filterUtils->composeRegex($statusFilters, '/'), 'excluded'));
        
        if ($shouldAddExcludes) {
            $unfilteredLoader = $loaderFactory->create($unfilteredPool, $pluginConfig, $isDevMode);

            $allPatches = $unfilteredLoader->loadFromPackagesRepository($repository);
            
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
    
    private function createConfigWithEnabledSources(Composer $composer)
    {
        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();

        $defaultValues = $configDefaults->getPatcherConfig();

        $sourceKeys = array_keys($defaultValues[Config::PATCHER_SOURCES]);

        $pluginConfig = array(
            Config::PATCHER_SOURCES => array_fill_keys($sourceKeys, true)
        );

        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer);
        
        return $configFactory->create(array($pluginConfig));
    }
    
    private function createStateGenerator(Composer $composer)
    {
        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector(
            array($composer->getPackage())
        );

        return new \Vaimo\ComposerPatches\Repository\StateGenerator(
            $packageCollector
        );
    }
    
    private function createQueueGenerator(Composer $composer, Config $config, ListResolver $listResolver)
    {
        $stateAnalyserFactory = new \Vaimo\ComposerPatches\Factories\RepositoryStateAnalyserFactory(
            $composer
        );
        
        $changesListResolver = new ListResolvers\ChangesListResolver($listResolver);

        $stateAnalyser = $stateAnalyserFactory->create($config);
        
        return new \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator(
            $changesListResolver,
            $stateAnalyser
        );
    }
    
    private function createLoaderPool(array $componentUpdates = array())
    {
        $componentPool = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $this->getComposer(),
            $this->getIO()
        );

        foreach ($componentUpdates as $componentName => $replacement) {
            $componentPool->registerComponent($componentName, $replacement);
        }
        
        return $componentPool;
    }
    
    private function generateOutput(OutputInterface $output, array $list)
    {
        $statusConfig = new \Vaimo\ComposerPatches\Package\PatchApplier\StatusConfig();

        $statusDecorators = $statusConfig->getLabels();
        
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
                } elseif ($owner) {
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
