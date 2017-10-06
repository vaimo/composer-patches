<?php
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Environment;

class RepositoryManagerFactory
{
    public function createForEvent($event)
    {
        $composer = $event->getComposer();
        $config = $composer->getConfig();
        $rootPackage = $composer->getPackage();
        $installationManager = $composer->getInstallationManager();
        
        $vendorRoot = $config->get('vendor-dir');
        $extraInfo = $rootPackage->getExtra();

        $io = $event->getIO();

        $logger = new \Vaimo\ComposerPatches\Logger($io);
        $downloader = new \Composer\Util\RemoteFilesystem($io, $config);

        $patchesManager = new \Vaimo\ComposerPatches\Managers\PatchesManager(
            $composer->getEventDispatcher(),
            $downloader,
            $logger,
            $vendorRoot
        );

        $loaders = array(
            PluginConfig::LIST => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
            PluginConfig::FILE => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
        );

        if ($event->isDevMode()) {
            $loaders = array_replace($loaders, array(
                PluginConfig::DEV_LIST => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
                PluginConfig::DEV_FILE => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
            ));
        }
        
        if (getenv(Environment::PREFER_OWNER)) {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\VendorConfigExtractor(
                $installationManager
            );
        } else {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\InstalledConfigExtractor();
        }

        $collector = new \Vaimo\ComposerPatches\Patch\Collector(
            $loaders,
            $infoExtractor
        );

        $patchProcessors = array(
            new \Vaimo\ComposerPatches\Patch\DefinitionProcessors\GlobalExcluder($extraInfo),
            new \Vaimo\ComposerPatches\Patch\DefinitionProcessors\LocalExcluder(),
            new \Vaimo\ComposerPatches\Patch\DefinitionProcessors\PathNormalizer($installationManager),
            new \Vaimo\ComposerPatches\Patch\DefinitionProcessors\ConstraintsApplier($extraInfo),
            new \Vaimo\ComposerPatches\Patch\DefinitionProcessors\Validator(),
            new \Vaimo\ComposerPatches\Patch\DefinitionProcessors\Simplifier(),
        );
        
        $packagesManager = new \Vaimo\ComposerPatches\Managers\PackagesManager(
            $rootPackage,
            $collector,
            $patchProcessors,
            $vendorRoot
        );
        
        return new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $installationManager,
            $rootPackage,
            $patchesManager,
            $packagesManager,
            $logger
        );
    }
}
