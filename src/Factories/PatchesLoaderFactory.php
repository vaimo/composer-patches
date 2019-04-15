<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch;
use Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;
use Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;
use Vaimo\ComposerPatches\Patch\SourceLoaders;
use Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool as LoaderComponents;

class PatchesLoaderFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @param \Composer\Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * 
     * @param LoaderComponents $loaderComponentsPool
     * @param PluginConfig $pluginConfig
     * @param bool $devMode
     * @return Patch\DefinitionList\Loader
     */
    public function create(LoaderComponents $loaderComponentsPool, PluginConfig $pluginConfig, $devMode = false) 
    {
        $composer = $this->composer;

        $installationManager = $composer->getInstallationManager();

        $rootPackage = $composer->getPackage();

        $composerConfig = clone $composer->getConfig();
        $patcherConfig = $pluginConfig->getPatcherConfig();
        
        $composerConfig->merge(array(
            'config' => array('secure-http' => $patcherConfig[PluginConfig::PATCHER_SECURE_HTTP])
        ));

        $loaders = array(
            PluginConfig::DEFINITIONS_LIST => new SourceLoaders\PatchList(),
            PluginConfig::DEFINITIONS_FILE => new SourceLoaders\PatchesFile($installationManager),
            PluginConfig::DEFINITIONS_SEARCH => new SourceLoaders\PatchesSearch(
                $installationManager,
                $devMode
            )
        );

        if ($devMode) {
            $loaders = array_replace($loaders, array(
                PluginConfig::DEV_DEFINITIONS_LIST => $loaders[PluginConfig::DEFINITIONS_LIST],
                PluginConfig::DEV_DEFINITIONS_FILE => $loaders[PluginConfig::DEFINITIONS_FILE]
            ));
        }

        $exploderComponents = array(
            new ExploderComponents\LabelVersionConfigComponent(),
            new ExploderComponents\VersionConfigComponent(),
            new ExploderComponents\VersionRangesConfigComponent(),
            new ExploderComponents\ComplexItemComponent(),
            new ExploderComponents\SequenceVersionConfigComponent(),
            new ExploderComponents\SequenceItemComponent(),
            new ExploderComponents\GroupVersionConfigComponent()
        );

        $definitionExploder = new Patch\Definition\Exploder($exploderComponents);

        $normalizerComponents = array(
            new NormalizerComponents\DefaultValuesComponent(),
            new NormalizerComponents\BaseComponent(),
            new NormalizerComponents\DependencyComponent(),
            new NormalizerComponents\PathComponent(),
            new NormalizerComponents\BasePathComponent(),
            new NormalizerComponents\UrlComponent(),
            new NormalizerComponents\SkipComponent(),
            new NormalizerComponents\SequenceComponent(),
            new NormalizerComponents\PatcherConfigComponent()
        );

        $definitionNormalizer = new Patch\Definition\Normalizer($normalizerComponents);

        $listNormalizer = new Patch\ListNormalizer(
            $definitionExploder,
            $definitionNormalizer
        );

        $configReaderFactory = new \Vaimo\ComposerPatches\Factories\PatcherConfigReaderFactory(
            $this->composer
        );

        $configReader = $configReaderFactory->create($pluginConfig);
        
        $patchesCollector = new Patch\Collector(
            $listNormalizer,
            $configReader,
            $loaders
        );

        $loaderComponents = $loaderComponentsPool->getList($pluginConfig);

        $srcResolverFactory = new \Vaimo\ComposerPatches\Factories\SourcesResolverFactory(
            $this->composer
        );

        return new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader(
            new \Vaimo\ComposerPatches\Package\Collector(
                array($rootPackage)
            ),
            $patchesCollector,
            $srcResolverFactory->create($pluginConfig),
            $loaderComponents
        );
    }
}
