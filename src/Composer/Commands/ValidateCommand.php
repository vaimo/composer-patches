<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

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
            'Apply patches based on information directly from packages in vendor folder'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $io = $this->getIO();

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

        $loaderComponentsPool->registerComponent('constraints', false);

        $patchesLoader = $loaderFactory->create($loaderComponentsPool, $config, true);

        $patches = array_filter($patchesLoader->loadFromPackagesRepository($repository));

        $patchesWithTargets = array_reduce($patches, function (array $result, array $items) {
            return array_merge(
                $result,
                array_values(
                    array_map(function ($item) {
                        return $item['path'];
                    }, $items)
                )
            );
        }, []);

        // @todo: get packages that own patches

        // @todo: do recursive search for .patch files

        // @todo: intersect

        // @todo: report

    }
}
