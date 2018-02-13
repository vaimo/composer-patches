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
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;
    }
    
    public function create(
        \Composer\Composer $composer, PluginConfig $pluginConfig, array $targets = array(), $filters = array()
    ) {
        $installationManager = $composer->getInstallationManager();
            
        $eventDispatcher = $composer->getEventDispatcher();
        $rootPackage = $composer->getPackage();
        $composerConfig = $composer->getConfig();

        $vendorRoot = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);
        
        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver($installationManager);
        
        if ($pluginConfig->shouldExitOnFirstFailure()) {
            $failureHandler = new FailureHandlers\FatalHandler($this->logger);    
        } else {
            $failureHandler = new FailureHandlers\GracefulHandler($this->logger);
        }
        
        $patchApplier = new \Vaimo\ComposerPatches\Patch\Applier(
            $this->logger, 
            $pluginConfig->getPatcherConfig()
        );
        
        $packagePatchApplier = new \Vaimo\ComposerPatches\Package\PatchApplier(
            $packageInfoResolver,
            $eventDispatcher,
            $failureHandler,
            $this->logger,
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

        $queueGenerator = new \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator(
            $repositoryAnalyser,
            $targets,
            $filters
        );

        $patcherStateManager = new \Vaimo\ComposerPatches\Managers\PatcherStateManager();
        
        $repositoryManager = new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $this->logger->getOutputInstance(),
            $installationManager
        );
        
        return new \Vaimo\ComposerPatches\Repository\PatchesApplier(
            $packageCollector,
            $repositoryManager,
            $packagePatchApplier,
            $queueGenerator,
            $patcherStateManager,
            $this->logger
        );
    }
}
