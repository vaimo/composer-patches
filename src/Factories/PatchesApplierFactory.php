<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Managers\PatcherStateManager;

class PatchesApplierFactory
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\IO\IOInterface $io
    ) {
        $this->io = $io;
    }
    
    public function create(\Composer\Composer $composer, PatcherStateManager $patcherStateManager)
    {
        $patcherConfigData = $composer->getPackage()->getExtra();
        
        $installationManager = $composer->getInstallationManager();
        $composerConfig = $composer->getConfig();
        $eventDispatcher = $composer->getEventDispatcher();
        
        $vendorRoot = $composerConfig->get('vendor-dir');
        
        $pluginConfig = new \Vaimo\ComposerPatches\Config();
        $rootPackage = $composer->getPackage();

        $logger = new \Vaimo\ComposerPatches\Logger($this->io);
        $downloader = new \Composer\Util\RemoteFilesystem($this->io, $composerConfig);
        
        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver($installationManager);
        
        if ($pluginConfig->shouldExitOnFirstFailure()) {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\FatalHandler($logger);    
        } else {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\GracefulHandler($logger);
        }

        $applierConfig = $pluginConfig->getApplierConfig(
            isset($patcherConfigData[PluginConfig::PATCHER_CONFIG])
            && is_array($patcherConfigData[PluginConfig::PATCHER_CONFIG]) ?
                $patcherConfigData[PluginConfig::PATCHER_CONFIG]
                : array()
        );
        
        $patchApplier = new \Vaimo\ComposerPatches\Patch\Applier($logger, $applierConfig);
        
        $packagePatchApplier = new \Vaimo\ComposerPatches\Package\PatchApplier(
            $packageInfoResolver,
            $eventDispatcher,
            $downloader,
            $failureHandler,
            $logger,
            $patchApplier,
            $vendorRoot
        );

        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector($rootPackage);

        if ($pluginConfig->shouldResetEverything()) {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\FullResetResolver();
        } else {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\MissingPatchesResolver();
        }
        
        $repositoryAnalyser = new \Vaimo\ComposerPatches\Repository\Analyser(
            $packageCollector,
            $packagesResolver
        );
        
        return new \Vaimo\ComposerPatches\Repository\PatchesApplier(
            $installationManager,
            $packagePatchApplier,
            $repositoryAnalyser,
            $patcherStateManager,
            $logger
        );
    }
}
