<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\Package\PackageInterface;
use Vaimo\ComposerPatches\Patch\Event;
use Vaimo\ComposerPatches\Events;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PatchApplier
{
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $packageInfoResolver;

    /**
     * @var \Composer\EventDispatcher\EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface
     */
    private $failureHandler;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Applier
     */
    private $patchApplier;

    /**
     * @var string
     */
    private $vendorRoot;

    /**
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     * @param \Composer\EventDispatcher\EventDispatcher $eventDispatcher
     * @param \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param \Vaimo\ComposerPatches\Patch\Applier $patchApplier
     * @param string $vendorRoot
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver,
        \Composer\EventDispatcher\EventDispatcher $eventDispatcher,
        \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler,
        \Vaimo\ComposerPatches\Logger $logger,
        \Vaimo\ComposerPatches\Patch\Applier $patchApplier,
        $vendorRoot
    ) {
        $this->packageInfoResolver = $packageInfoResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->failureHandler = $failureHandler;
        $this->logger = $logger;
        $this->patchApplier = $patchApplier;
        $this->vendorRoot = $vendorRoot;

        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }

    public function applyPatches(PackageInterface $package, array $patchesQueue)
    {
        if ($package instanceof \Composer\Package\RootPackage) {
            $installPath = $this->vendorRoot;
        } else {
            $installPath = $this->packageInfoResolver->getSourcePath($package);
        }
        
        $appliedPatches = array();

        foreach ($patchesQueue as $source => $patchInfo) {
            $path = $patchInfo[PatchDefinition::PATH];
            $label = $patchInfo[PatchDefinition::LABEL];
            $config = $patchInfo[PatchDefinition::CONFIG];
            
            $this->logger->writeRaw(
                '%s', 
                array(sprintf('<info>%s</info>: %s', $patchInfo[PatchDefinition::OWNER], $source))
            );

            $loggerIndentation = $this->logger->push();

            $this->logger->writeRaw(
                '<comment>%s</comment>', 
                array($label)
            );
            
            try {
                $this->eventDispatcher->dispatch(
                    Events::PRE_APPLY,
                    new Event(Events::PRE_APPLY, $package, $source, $label)
                );
                
                $this->patchApplier->applyFile($path, $installPath, $config);

                $appliedPatches[$source] = $patchInfo;

                $this->eventDispatcher->dispatch(
                    Events::POST_APPLY,
                    new Event(Events::POST_APPLY, $package, $source, $label)
                );
            } catch (\Exception $exception) {
                $this->logger->writeException($exception);
                
                $this->failureHandler->execute(
                    $exception->getMessage(),
                    $source
                );
            } finally {
                $this->logger->reset($loggerIndentation);
            }
        }

        return $appliedPatches;
    }
}
