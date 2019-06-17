<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch\FailureHandlers;
use Vaimo\ComposerPatches\Strategies;
use Vaimo\ComposerPatches\Interfaces\ListResolverInterface as ListResolver;
use Vaimo\ComposerPatches\Strategies\OutputStrategy;

class PatchesApplierFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;
    
    /**
     * @param \Composer\Composer $composer
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Composer\Composer $composer,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->composer = $composer;
        $this->logger = $logger;
    }

    public function create(PluginConfig $pluginConfig, ListResolver $listResolver, OutputStrategy $outputStrategy)
    {
        $composer = $this->composer;

        $installer = $composer->getInstallationManager();
        $downloader = $composer->getDownloadManager();

        $eventDispatcher = $composer->getEventDispatcher();
        $rootPackage = $composer->getPackage();
        $composerConfig = $composer->getConfig();

        $output = $this->logger->getOutputInstance();

        $vendorRoot = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $installer,
            $vendorRoot
        );

        $failureHandler = $pluginConfig->shouldExitOnFirstFailure()
            ? new FailureHandlers\FatalHandler()
            : new FailureHandlers\GracefulHandler($this->logger);

        $patchFileApplier = new \Vaimo\ComposerPatches\Patch\File\Applier(
            $this->logger,
            $pluginConfig->getPatcherConfig()
        );

        $patchInfoLogger = new \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger($this->logger);
        
        $packagePatchApplier = new \Vaimo\ComposerPatches\Package\PatchApplier(
            $eventDispatcher,
            $packageInfoResolver,
            $failureHandler,
            $patchFileApplier,
            $patchInfoLogger,
            $outputStrategy,
            $this->logger
        );

        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector(
            array($rootPackage)
        );
        
        $stateAnalyserFactory = new \Vaimo\ComposerPatches\Factories\RepositoryStateAnalyserFactory(
            $composer
        );

        $stateAnalyser = $stateAnalyserFactory->create();

        $queueGenerator = new \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator(
            $listResolver,
            $stateAnalyser
        );
        
        $patcherStateManager = new \Vaimo\ComposerPatches\Managers\PatcherStateManager();

        $packageResetStrategy = $pluginConfig->shouldForcePackageReset()
            ? new Strategies\Package\ForcedResetStrategy()
            : new Strategies\Package\DefaultResetStrategy($installer, $downloader);

        $repositoryManager = new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $output,
            $installer,
            $packageResetStrategy
        );

        return new \Vaimo\ComposerPatches\Repository\PatchesApplier(
            $packageCollector,
            $repositoryManager,
            $packagePatchApplier,
            $queueGenerator,
            $patcherStateManager,
            $patchInfoLogger,
            $outputStrategy,
            $this->logger
        );
    }
}
