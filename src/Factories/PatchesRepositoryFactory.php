<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;
use Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

class PatchesRepositoryFactory
{
    public function create(\Composer\Composer $composer, $devMode = false, array $patcherConfigData = array()) 
    {
        if (!$patcherConfigData) {
            $patcherConfigData = $composer->getPackage()->getExtra();
        }
        
        $packagesRepository = $composer->getRepositoryManager()->getLocalRepository();
        
        $installationManager = $composer->getInstallationManager();
        $pluginConfig = new \Vaimo\ComposerPatches\Config();
        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver($installationManager);
        $rootPackage = $composer->getPackage();

        $composerConfig = $composer->getConfig();
        $vendorRoot = $composerConfig->get('vendor-dir');
        
        $loaders = array(
            PluginConfig::LIST =>
                new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
            PluginConfig::FILE =>
                new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile($installationManager)
        );

        if ($devMode) {
            $loaders = array_replace($loaders, array(
                PluginConfig::DEV_LIST =>
                    new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
                PluginConfig::DEV_FILE =>
                    new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile($installationManager)
            ));
        }
        
        if ($pluginConfig->shouldPreferOwnerPackageConfig()) {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\VendorConfigExtractor(
                $packageInfoResolver
            );
        } else {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\InstalledConfigExtractor();
        }

        $exploderComponents = array(
            new ExploderComponents\VersionConfigComponent(),
            new ExploderComponents\ComplexItemComponent(),
            new ExploderComponents\SequenceVersionConfigComponent(),
            new ExploderComponents\SequenceItemComponent(),
            new ExploderComponents\GroupVersionConfigComponent()
        );

        $definitionExploder = new \Vaimo\ComposerPatches\Patch\Definition\Exploder($exploderComponents);
        $definitionNormalizer = new \Vaimo\ComposerPatches\Patch\Definition\Normalizer();
        
        $listNormalizer = new \Vaimo\ComposerPatches\Patch\ListNormalizer(
            $definitionExploder,
            $definitionNormalizer
        );
        
        $patchesCollector = new \Vaimo\ComposerPatches\Patch\Collector(
            $listNormalizer,
            $infoExtractor,
            $loaders
        );
        
        $loaderComponents = array(
            new LoaderComponents\BundleComponent($rootPackage),
            new LoaderComponents\GlobalExcludeComponent($patcherConfigData),
            new LoaderComponents\LocalExcludeComponent(),
            new LoaderComponents\CustomExcludeComponent($pluginConfig->getSkippedPackages()),
            new LoaderComponents\PathNormalizerComponent($installationManager),
            new LoaderComponents\ConstraintsComponent($patcherConfigData),
            new LoaderComponents\ValidatorComponent(),
            new LoaderComponents\SimplifierComponent(),
            new LoaderComponents\TargetsResolverComponent($packageInfoResolver)
        );

        $packagesCollector = new \Vaimo\ComposerPatches\Package\Collector($rootPackage);

        $definitionListLoader = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader(
            $packagesCollector,
            $patchesCollector,
            $loaderComponents,
            $vendorRoot
        );
        
        $patcherConfig = new \Vaimo\ComposerPatches\Patch\Config(
            $patcherConfigData,
            array_keys($loaders)
        );
        
        return new \Vaimo\ComposerPatches\Repositories\PatchesRepository(
            $rootPackage,
            $packagesRepository,
            $patcherConfig,
            $packagesCollector,
            $definitionListLoader
        );
    }
}
