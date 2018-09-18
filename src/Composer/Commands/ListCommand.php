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
        $withExcluded = $input->getOption('excluded');

        $filters = array(
            PatchDefinition::SOURCE => $input->getOption('filter'),
            PatchDefinition::TARGETS => $input->getArgument('targets')
        );

        $statusFilters = $input->getOption('status');
        
        $config = array(
            \Vaimo\ComposerPatches\Config::PATCHER_SOURCES => array(
                'project' => true,
                'packages' => true,
                'vendors' => true
            )
        );
        
        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();

        $filteredPool = $this->createLoaderPool();
        $unfilteredPool = $this->createLoaderPool(array('constraints', 'local-exclude', 'global-exclude'));
        
        $patchListManager = new \Vaimo\ComposerPatches\Managers\PatchListManager(
            new ListResolvers\FilteredListResolver($filters)
        );
        
        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer);
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);
        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector(array($composer->getPackage()));

        $configInstance = $configFactory->create(array($config));
        $repository = $composer->getRepositoryManager()->getLocalRepository();

        $packages = $packageCollector->collect($repository);
            
        $filteredPatchesLoader = $loaderFactory->create($filteredPool, $configInstance, $isDevMode);
        $filteredPatches = $filteredPatchesLoader->loadFromPackagesRepository($repository);
        
        $patches = $patchListManager->getPatchesWithStatuses($filteredPatches, $packages, $statusFilters);
        
        if ($withExcluded) {
            if (!$statusFilters || preg_match($filterUtils->composeRegex($statusFilters, '/'), 'excluded')) {
                $patchesLoader = $loaderFactory->create($unfilteredPool, $configInstance, $isDevMode);
                $allPatches = $patchesLoader->loadFromPackagesRepository($repository);

                $excludedPatches = $patchListManager->updateStatuses(
                    $patchListManager->getPatchListDifference($allPatches, $filteredPatches),
                    'excluded'
                );

                $patches = array_replace_recursive(
                    $patches,
                    $patchListManager->updateStatuses($excludedPatches, 'excluded')
                );
            }
        }
        
        $this->generateOutput($output, $patches);
    }
    
    private function createLoaderPool(array $excludedComponentNames = array())
    {
        $composer = $this->getComposer();
        $io = $this->getIO();

        $componentPool = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composer,
            $io
        );

        foreach ($excludedComponentNames as $componentName) {
            $componentPool->registerComponent($componentName, false);
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
                
                $output->writeln(sprintf('  ~ <info>%s</info>: %s%s', $owner, $path, $statusLabel));
                
                foreach (explode(PHP_EOL, $info[PatchDefinition::LABEL]) as $line) {
                    $output->writeln(
                        sprintf('    <comment>%s</comment>', $line)
                    );
                }
            }

            $output->writeln('');
        }
    }
}
