<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Input\InputArgument;
use Vaimo\ComposerPatches\Patch\Definition as Patch;

use Composer\Composer;

use Vaimo\ComposerPatches\Interfaces\ListResolverInterface as ListResolver;
use Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;
use Vaimo\ComposerPatches\Config;
use Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;
use Vaimo\ComposerPatches\Composer\Context as ComposerContext;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ListCommand extends \Composer\Command\BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('patch:list');

        $this->setDescription('List all registered and eligible (based on project config) patches');

        $this->addArgument(
            'targets',
            InputArgument::IS_ARRAY,
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
            'Match specific statuses (changed, new, applied, removed)',
            array()
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
            Patch::SOURCE => $input->getOption('filter'),
            Patch::TARGETS => $input->getArgument('targets')
        );

        $statusFilters = array_map(
            'strtolower',
            array_filter((array)$input->getOption('status'))
        );

        $contextFactory = new \Vaimo\ComposerPatches\Factories\ComposerContextFactory($composer);
        $composerContext = $contextFactory->create();

        $pluginConfig = $this->createConfigWithEnabledSources($composerContext);

        $filteredPool = $this->createLoaderPool($composerContext);

        $listResolver = new ListResolvers\FilteredListResolver($filters);
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);

        $repository = $composer->getRepositoryManager()->getLocalRepository();

        $repoStateGenerator = $this->createStateGenerator($composer);
        $queueGenerator = $this->createQueueGenerator($listResolver);
        $filteredLoader = $loaderFactory->create($filteredPool, $pluginConfig, $isDevMode);

        $filteredPatches = $filteredLoader->loadFromPackagesRepository($repository);
        $repositoryState = $repoStateGenerator->generate($repository);

        $applyQueue = $queueGenerator->generateApplyQueue($filteredPatches, $repositoryState);
        $removeQueue = $queueGenerator->generateRemovalQueue($applyQueue, $repositoryState);
        $applyQueue = array_map('array_filter', $applyQueue);

        $patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
        $patchListUpdater = new \Vaimo\ComposerPatches\Patch\DefinitionList\Updater();

        $filteredPatches = $this->composerFilteredPatchesList(
            $filteredPatches,
            $applyQueue,
            $removeQueue,
            $withAffected,
            $filters,
            $statusFilters
        );

        $patches = array_filter($filteredPatches);

        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();

        $shouldAddExcludes = $withExcluded
            && (
                empty($statusFilters)
                || preg_match($filterUtils->composeRegex($statusFilters, '/'), 'excluded')
            );

        if ($shouldAddExcludes) {
            $unfilteredPool = $this->createUnfilteredPatchLoaderPool($composerContext);

            $unfilteredLoader = $loaderFactory->create($unfilteredPool, $pluginConfig, $isDevMode);

            $allPatches = $unfilteredLoader->loadFromPackagesRepository($repository);

            $patchesQueue = $listResolver->resolvePatchesQueue($allPatches);

            $excludedPatches = $patchListUpdater->updateStatuses(
                array_filter($patchListUtils->diffListsByPath($patchesQueue, $filteredPatches)),
                'excluded'
            );

            $patches = array_replace_recursive(
                $patches,
                $patchListUpdater->updateStatuses($excludedPatches, 'excluded')
            );

            array_walk($patches, function (array &$group) {
                ksort($group);
            }, $patches);
        }

        if ($beBrief) {
            $patches = $patchListUpdater->embedInfoToItems($patches, array(
                Patch::LABEL => false,
                Patch::OWNER => false
            ));
        }

        $this->generateOutput($output, $patches);
        
        return self::SUCCESS;
    }

    private function createUnfilteredPatchLoaderPool(\Vaimo\ComposerPatches\Composer\Context $composerContext)
    {
        $composer = $composerContext->getLocalComposer();

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $composer->getInstallationManager(),
            $composer->getConfig()->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR)
        );

        $componentOverrides =  array(
            'constraints' => false,
            'platform' => false,
            'local-exclude' => false,
            'root-patch' => false,
            'global-exclude' => false,
            'targets-resolver' => new LoaderComponents\TargetsResolverComponent($packageInfoResolver, true)
        );

        return $this->createLoaderPool($composerContext, $componentOverrides);
    }

    private function composerFilteredPatchesList($patches, $additions, $removals, $withAffected, $filters, $statuses)
    {
        $hasFilers = (bool)array_filter($filters);

        $listResolver = new ListResolvers\FilteredListResolver($filters);

        $patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
        $patchListUpdater = new \Vaimo\ComposerPatches\Patch\DefinitionList\Updater();

        $filteredPatches = $patchListUtils->mergeLists($patches, $removals);

        if ($withAffected) {
            $additions = $patchListUpdater->embedInfoToItems(
                $additions,
                array(Patch::STATUS => 'affected'),
                true
            );
        }

        $filteredPatches = $patchListUtils->mergeLists(
            $filteredPatches,
            $patchListUtils->intersectListsByName($additions, $filteredPatches)
        );

        $filteredPatches = $patchListUpdater->embedInfoToItems(
            $filteredPatches,
            array(Patch::STATUS => 'applied'),
            true
        );

        if ($hasFilers) {
            $filteredPatches = $listResolver->resolvePatchesQueue($filteredPatches);
        }

        if (!empty($statuses)) {
            $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();

            $filteredPatches = $patchListUtils->applyDefinitionKeyValueFilter(
                $filteredPatches,
                $filterUtils->composeRegex($statuses, '/'),
                Patch::STATUS
            );
        }

        return $filteredPatches;
    }

    private function createConfigWithEnabledSources(\Vaimo\ComposerPatches\Composer\Context $composerContext)
    {
        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();

        $defaultValues = $configDefaults->getPatcherConfig();

        $sourceKeys = array();

        if (isset($defaultValues[Config::PATCHER_SOURCES]) && is_array($defaultValues[Config::PATCHER_SOURCES])) {
            $sourceKeys = array_keys((array)$defaultValues[Config::PATCHER_SOURCES]);
        }

        $pluginConfig = array(
            Config::PATCHER_SOURCES => array_fill_keys($sourceKeys, true)
        );

        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composerContext);

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

    private function createQueueGenerator(ListResolver $listResolver)
    {
        $changesListResolver = new ListResolvers\ChangesListResolver($listResolver);
        $stateAnalyser = new \Vaimo\ComposerPatches\Repository\State\Analyser();

        return new \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator(
            $changesListResolver,
            $stateAnalyser
        );
    }

    private function createLoaderPool(ComposerContext $composerContext, array $componentUpdates = array())
    {
        $componentPool = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composerContext,
            $this->getIO(),
            true
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
                $owner = $info[Patch::OWNER];
                $patchInfoLabel = $this->createStatusLabel($path, $info, $statusDecorators);
                $output->writeln($owner ? sprintf('  ~ %s', $patchInfoLabel) : $patchInfoLabel);

                $descriptionLines = array_filter(
                    explode(PHP_EOL, $info[Patch::LABEL])
                );

                foreach ($descriptionLines as $line) {
                    $output->writeln(sprintf('    <comment>%s</comment>', $line));
                }
            }

            $output->writeln('');
        }
    }

    private function createStatusLabel($path, $info, array $statusDecorators)
    {
        $status = isset($info[Patch::STATUS])
            ? $info[Patch::STATUS]
            : Patch::STATUS_UNKNOWN;

        $owner = $info[Patch::OWNER];

        $stateDecorator = $statusDecorators[$status];

        if ($status === Patch::STATUS_ERRORS) {
            $stateDecorator = sprintf(
                $stateDecorator,
                $info[Patch::STATE_LABEL] ? $info[Patch::STATE_LABEL] : 'ERROR'
            );
        }

        $statusLabel = sprintf(' [%s]', $stateDecorator);

        if ($owner && $owner !== Patch::OWNER_UNKNOWN) {
            return sprintf('<info>%s</info>: %s%s', $owner, $path, $statusLabel);
        }

        return sprintf('%s%s', $path, $statusLabel);
    }
}
