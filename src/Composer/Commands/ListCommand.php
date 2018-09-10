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
        $io = $this->getIO();
        
        $isDevMode = !$input->getOption('no-dev');

        $filters = array(
            PatchDefinition::SOURCE => $input->getOption('filter'),
            PatchDefinition::TARGETS => $input->getArgument('targets')
        );
        
        $config = array(
            \Vaimo\ComposerPatches\Config::PATCHER_SOURCES => array(
                'project' => true,
                'packages' => true,
                'vendors' => true
            )
        );

        $loaderComponentsPool = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composer,
            $io
        );
        
        $patchListManager = new \Vaimo\ComposerPatches\Managers\PatchListManager(
            new ListResolvers\FilteredListResolver($filters)
        );
        
        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer);
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);
        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector(array($composer->getPackage()));

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        
        $statusFilters = $input->getOption('status');

        $patchesLoader = $loaderFactory->create(
            $loaderComponentsPool, 
            $configFactory->create(array($config)), 
            $isDevMode
        );

        $patches = $patchListManager->getPatchesWithStatuses(
            $patchesLoader->loadFromPackagesRepository($repository), 
            $packageCollector->collect($repository), 
            $statusFilters
        );
        
        $this->generateOutput($output, $patches);
    }
    
    private function generateOutput(OutputInterface $output, array $list)
    {
        $stateDecorators = array(
            'new' => '<info>NEW</info>',
            'changed' => '<info>CHANGED</info>',
            'applied' => '<fg=white;options=bold>APPLIED</>',
            'removed' => '<fg=red>REMOVED</>',
            'unknown' => 'UNKNOWN'
        );
        
        foreach ($list as $packageName => $patches) {
            $output->writeln(sprintf('<info>%s</info>', $packageName));

            foreach ($patches as $path => $info) {
                $state = isset($info[PatchDefinition::STATE]) 
                    ? $info[PatchDefinition::STATE] 
                    : 'unknown';

                $stateLabel = sprintf(' [%s]', $stateDecorators[$state]);
                $owner = $info[PatchDefinition::OWNER];
                
                $output->writeln(sprintf('  ~ <info>%s</info>: %s%s', $owner, $path, $stateLabel));
                $output->writeln(sprintf('    <comment>%s</comment>', $info[PatchDefinition::LABEL]));
            }

            $output->writeln('');
        }
    }
}
