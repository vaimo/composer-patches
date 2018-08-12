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
        $output->writeln('<info>Scanning packages for orphan patches ...</info>');

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

        $patches = $patchesLoader->loadFromPackagesRepository($repository);

        $patchesWithTargets = array_reduce($patches, function (array $result, array $items) {
            return array_merge(
                $result,
                array_values(
                    array_map(function ($item) {
                        return $item['path'];
                    }, $items)
                )
            );
        }, array());

        $sourcesResolverFactory = new \Vaimo\ComposerPatches\Factories\SourcesResolverFactory($composer);
        $packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();

        $sourcesResolver = $sourcesResolverFactory->create($config);

        $sources = $sourcesResolver->resolvePackages($repository);

        $repositoryUtils = new \Vaimo\ComposerPatches\Utils\RepositoryUtils();

        $pluginPackage = $repositoryUtils->resolveForNamespace($repository, __NAMESPACE__);
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
        $vendorRoot = $composer->getConfig()->get(ConfigKeys::VENDOR_DIR);
        $vendorPath = ltrim(
            substr($vendorRoot, strlen($projectRoot)),
            DIRECTORY_SEPARATOR
        );

        $fileMatches = array();
        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();

        $installPaths = array();
        foreach ($matches as $packageName => $package) {
            if ($package instanceof \Composer\Package\RootPackage) {
                $installPath = $projectRoot;
            } else {
                $installPath = $installationManager->getInstallPath($package);
            }

            $installPaths[$packageName] = $installPath;
        }

        foreach ($matches as $packageName => $package) {
            $installPath = $installPaths[$packageName];

            $skippedPaths = array(
                $installPath . DIRECTORY_SEPARATOR . $vendorPath,
                $installPath . DIRECTORY_SEPARATOR . '.hg',
                $installPath . DIRECTORY_SEPARATOR . '.git'
            );

            $filter = $filterUtils->composeRegex($filterUtils->invertRules($skippedPaths), '/');

            $filter = rtrim($filter, '/') . '.+\.patch' . '/i';

            $result = $fileSystemUtils->collectPathsRecursively($installPath, $filter);

            $fileMatches = array_replace($fileMatches, array_fill_keys($result, $packageName));
        }

        $orphanFiles = array_diff_key($fileMatches, array_flip($patchesWithTargets));

        $groups = array_fill_keys(array_keys($matches), array());

        foreach ($orphanFiles as $path => $ownerName)  {
            $installPath = $installPaths[$ownerName];
            $groups[$ownerName][] = ltrim(substr($path, strlen($installPath)), DIRECTORY_SEPARATOR);
        }

        if ($groups = array_filter($groups)) {
            foreach ($groups as $packageName => $paths) {
                $output->writeln(sprintf('- <info>%s</info>', $packageName));

                foreach ($paths as $path) {
                    $output->writeln(sprintf('  ~ %s', $path));
                }
            }

            $output->writeln('<error>Orphans found!</error>');

            exit(1);
        } else {
            $output->writeln('<info>Done</info>');
        }
    }
}
