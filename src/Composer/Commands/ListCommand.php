<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Environment;

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

        $bootstrapFactory = new \Vaimo\ComposerPatches\Factories\BootstrapFactory($composer, $io);

        $isDevMode = !$input->getOption('no-dev');

        $filters = array(
            PatchDefinition::SOURCE => $input->getOption('filter'),
            PatchDefinition::TARGETS => $input->getArgument('targets')
        );

        $listResolver = new ListResolvers\FilteredListResolver($filters);
        
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

        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer);
        $loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);

        $repository = $composer->getRepositoryManager()->getLocalRepository();

        $config = $configFactory->create(array($config));

        $patchesLoader = $loaderFactory->create($loaderComponentsPool, $config, $isDevMode);

        $patches = $patchesLoader->loadFromPackagesRepository($repository);
        
        $matches = $listResolver->resolvePatchesQueue($patches);

        foreach ($matches as $packageName => $patches) {
            $output->writeln(sprintf('<info>%s</info>', $packageName));

            foreach ($patches as $path => $info) {
                $output->writeln(sprintf('  ~ <info>%s</info>: %s', $info['owner'], $path));
                $output->writeln(sprintf('    <comment>%s</comment>', $info['label']));
            }

            $output->writeln('');
        }
    }
}
