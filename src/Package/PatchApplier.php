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

        foreach ($patchesQueue as $source => $info) {
            $this->logger->writeRaw(
                '<info>%s</info>: %s%s', 
                array(
                    $info[PatchDefinition::OWNER], 
                    $source,
                    $info[PatchDefinition::CHANGED] ? ' [<info>NEW</info>]' : '' 
                )
            );
            
            $loggerIndentation = $this->logger->push();

            $this->logger->writeRaw('<comment>%s</comment>', array($info[PatchDefinition::LABEL]));            
            try {
                $this->eventDispatcher->dispatch(
                    Events::PRE_APPLY,
                    new Event(Events::PRE_APPLY, $package, $source, $info[PatchDefinition::LABEL])
                );
                
                $this->patchApplier->applyFile(
                    $info[PatchDefinition::PATH], 
                    $installPath, 
                    $info[PatchDefinition::CONFIG]
                );

                $appliedPatches[$source] = $info;

                $this->eventDispatcher->dispatch(
                    Events::POST_APPLY,
                    new Event(Events::POST_APPLY, $package, $source, $info[PatchDefinition::LABEL])
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
