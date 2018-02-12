<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch\FailureHandlers;
use Vaimo\ComposerPatches\Patch\PackageResolvers;

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
    
    public function create(\Composer\Composer $composer, PluginConfig $pluginConfig)
    {
        $patcherStateManager = new \Vaimo\ComposerPatches\Managers\PatcherStateManager();
            
        $installationManager = $composer->getInstallationManager();
        $eventDispatcher = $composer->getEventDispatcher();
        $rootPackage = $composer->getPackage();
        $composerConfig = $composer->getConfig();

        $vendorRoot = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);

        $logger = new \Vaimo\ComposerPatches\Logger($this->io);
        
        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver($installationManager);
        
        if ($pluginConfig->shouldExitOnFirstFailure()) {
            $failureHandler = new FailureHandlers\FatalHandler($logger);    
        } else {
            $failureHandler = new FailureHandlers\GracefulHandler($logger);
        }
        
        $patchApplier = new \Vaimo\ComposerPatches\Patch\Applier(
            $logger, 
            $pluginConfig->getPatcherConfig()
        );
        
        $packagePatchApplier = new \Vaimo\ComposerPatches\Package\PatchApplier(
            $packageInfoResolver,
            $eventDispatcher,
            $failureHandler,
            $logger,
            $patchApplier,
            $vendorRoot
        );

        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector(array($rootPackage));

        if ($pluginConfig->shouldResetEverything()) {
            $packagesResolver = new PackageResolvers\FullResetResolver();
        } else {
            $packagesResolver = new PackageResolvers\MissingPatchesResolver();
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
