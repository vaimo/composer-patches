<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;
use Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;
use Vaimo\ComposerPatches\Patch\SourceLoaders;
use Vaimo\ComposerPatches\Package\ConfigExtractors;
use Vaimo\ComposerPatches\Patch;

class PatchesRepositoryFactory
{
    public function create(\Composer\Composer $composer, array $config, $devMode = false) 
    {
        $packagesRepository = $composer->getRepositoryManager()->getLocalRepository();
        $installationManager = $composer->getInstallationManager();
        $rootPackage = $composer->getPackage();
        $composerConfig = $composer->getConfig();
        
        $vendorRoot = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);
        
        $pluginConfig = new PluginConfig();
        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver($installationManager);
        
        $loaders = array(
            PluginConfig::LIST => new SourceLoaders\PatchList(),
            PluginConfig::FILE => new SourceLoaders\PatchesFile($installationManager)
        );

        if ($devMode) {
            $loaders = array_replace($loaders, array(
                PluginConfig::DEV_LIST => new SourceLoaders\PatchList(),
                PluginConfig::DEV_FILE => new SourceLoaders\PatchesFile($installationManager)
            ));
        }
        
        if ($pluginConfig->shouldPreferOwnerPackageConfig()) {
            $infoExtractor = new ConfigExtractors\VendorConfigExtractor($packageInfoResolver);
        } else {
            $infoExtractor = new ConfigExtractors\InstalledConfigExtractor();
        }

        $exploderComponents = array(
            new ExploderComponents\VersionConfigComponent(),
            new ExploderComponents\ComplexItemComponent(),
            new ExploderComponents\SequenceVersionConfigComponent(),
            new ExploderComponents\SequenceItemComponent(),
            new ExploderComponents\GroupVersionConfigComponent()
        );

        $definitionExploder = new Patch\Definition\Exploder($exploderComponents);
        $definitionNormalizer = new Patch\Definition\Normalizer();
        
        $listNormalizer = new Patch\ListNormalizer(
            $definitionExploder,
            $definitionNormalizer
        );
        
        $patchesCollector = new Patch\Collector(
            $listNormalizer,
            $infoExtractor,
            $loaders
        );
        
        $loaderComponents = array(
            new LoaderComponents\BundleComponent($rootPackage),
            new LoaderComponents\GlobalExcludeComponent($config),
            new LoaderComponents\LocalExcludeComponent(),
            new LoaderComponents\CustomExcludeComponent($pluginConfig->getSkippedPackages()),
            new LoaderComponents\PathNormalizerComponent($installationManager),
            new LoaderComponents\ConstraintsComponent($config),
            new LoaderComponents\ValidatorComponent(),
            new LoaderComponents\SimplifierComponent(),
            new LoaderComponents\TargetsResolverComponent($packageInfoResolver)
        );

        $packagesCollector = new \Vaimo\ComposerPatches\Package\Collector($rootPackage);

        $definitionListLoader = new Patch\DefinitionList\Loader(
            $packagesCollector,
            $patchesCollector,
            $loaderComponents,
            $vendorRoot
        );
        
        $patcherConfig = new Patch\Config(
            $config,
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
