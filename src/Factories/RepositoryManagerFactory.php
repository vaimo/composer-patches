<?php
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch\DefinitionProcessors;

class RepositoryManagerFactory
{
    public function createForEvent(\Composer\Script\Event $event)
    {
        $composer = $event->getComposer();
        $composerConfig = $composer->getConfig();

        $config = new \Vaimo\ComposerPatches\Config();
        
        $rootPackage = $composer->getPackage();
        $installationManager = $composer->getInstallationManager();
        
        $vendorRoot = $composerConfig->get('vendor-dir');
        $extraInfo = $rootPackage->getExtra();

        $io = $event->getIO();

        $logger = new \Vaimo\ComposerPatches\Logger($io);
        $downloader = new \Composer\Util\RemoteFilesystem($io, $composerConfig);
        
        if ($config->shouldExitOnFirstFailure()) {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\FatalHandler($logger);    
        } else {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\GracefulHandler($logger);
        }
        
        $patchesManager = new \Vaimo\ComposerPatches\Managers\PatchesManager(
            $composer->getEventDispatcher(),
            $downloader,
            $failureHandler,
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
        
        if ($config->shouldPreferOwnerPackageConfig()) {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\VendorConfigExtractor(
                $installationManager
            );
        } else {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\InstalledConfigExtractor();
        }

        $patchCollector = new \Vaimo\ComposerPatches\Patch\Collector($infoExtractor, $loaders);
        
        if ($config->shouldResetEverything()) {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\FullResetResolver();
        } else {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\MissingPatchesResolver();
        }
        
        $patchProcessors = array(
            new DefinitionProcessors\GlobalExcluder($extraInfo),
            new DefinitionProcessors\LocalExcluder(),
            new DefinitionProcessors\CustomExcluder($config->getSkippedPackages()),
            new DefinitionProcessors\PathNormalizer($installationManager),
            new DefinitionProcessors\ConstraintsApplier($extraInfo),
            new DefinitionProcessors\Validator(),
            new DefinitionProcessors\Simplifier(),
        );
        
        $packagesManager = new \Vaimo\ComposerPatches\Managers\PackagesManager(
            $rootPackage,
            $patchCollector,
            $patchProcessors,
            $vendorRoot
        );
        
        return new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $installationManager,
            $rootPackage,
            $patchesManager,
            $packagesManager,
            $packagesResolver,
            $logger
        );
    }
}
