<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch\FailureHandlers;
use Vaimo\ComposerPatches\Patch\PackageResolvers;
use Vaimo\ComposerPatches\Strategies;

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

    public function create(PluginConfig $pluginConfig, $filters = array())
    {
        $composer = $this->composer;

        $installationManager = $composer->getInstallationManager();
        $downloadManager = $composer->getDownloadManager();

        $eventDispatcher = $composer->getEventDispatcher();
        $rootPackage = $composer->getPackage();
        $composerConfig = $composer->getConfig();

        $vendorRoot = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver($installationManager);

        if ($pluginConfig->shouldExitOnFirstFailure()) {
            $failureHandler = new FailureHandlers\FatalHandler();
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

        $missingItemsAnalyser = new \Vaimo\ComposerPatches\Repository\Analyser(
            $packageCollector,
            new \Vaimo\ComposerPatches\Patch\PackageResolvers\MissingPatchesResolver()
        );
        
        $listResolver = new \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver(
            $repositoryAnalyser,
            $missingItemsAnalyser
        );
        
        $queueGenerator = new \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator(
            $listResolver,
            $filters
        );

        $patcherStateManager = new \Vaimo\ComposerPatches\Managers\PatcherStateManager();

        if ($pluginConfig->shouldForcePackageReset()) {
            $packageResetStrategy = new Strategies\Package\ForcedResetStrategy();
        } else {
            $packageResetStrategy = new Strategies\Package\DefaultResetStrategy(
                $installationManager,
                $downloadManager
            );
        }

        $repositoryManager = new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $this->logger->getOutputInstance(),
            $installationManager,
            $packageResetStrategy
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
