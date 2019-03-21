<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\Package\PackageInterface;

use Vaimo\ComposerPatches\Patch\Event;
use Vaimo\ComposerPatches\Events;
use Vaimo\ComposerPatches\Patch\Definition as Patch;

class PatchApplier
{
    /**
     * @var \Composer\EventDispatcher\EventDispatcher
     */
    private $eventDispatcher;
    
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $packageInfoResolver;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface
     */
    private $failureHandler;

    /**
     * @var \Vaimo\ComposerPatches\Patch\File\Applier
     */
    private $patchApplier;

    /**
     * @var \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger
     */
    private $patchInfoLogger;

    /**
     * @var \Vaimo\ComposerPatches\Strategies\OutputStrategy 
     */
    private $outputStrategy;
    
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @param \Composer\EventDispatcher\EventDispatcher $eventDispatcher
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     * @param \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler
     * @param \Vaimo\ComposerPatches\Patch\File\Applier $patchApplier
     * @param \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger $patchInfoLogger
     * @param \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Composer\EventDispatcher\EventDispatcher $eventDispatcher,
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver,
        \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler,
        \Vaimo\ComposerPatches\Patch\File\Applier $patchApplier,
        \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger $patchInfoLogger,
        \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->packageInfoResolver = $packageInfoResolver;
        $this->failureHandler = $failureHandler;
        $this->patchApplier = $patchApplier;
        $this->patchInfoLogger = $patchInfoLogger;
        $this->outputStrategy = $outputStrategy;
        $this->logger = $logger;
    }
    
    public function applyPatches(PackageInterface $package, array $patchesQueue)
    {
        $appliedPatches = array();
        
        foreach ($patchesQueue as $source => $info) {
            $muteDepth = !$this->outputStrategy->shouldAllowForPatches(array($info)) 
                ? $this->logger->mute() 
                : null;

            $patchInfo = array_replace($info, array(Patch::SOURCE => $source));
            
            $this->patchInfoLogger->outputPatchSource($patchInfo);

            $loggerIndentation = $this->logger->push();

            $this->patchInfoLogger->outputPatchDescription($patchInfo);

            try {
                $result = $this->processPackagePatch($package, $patchInfo);
            } catch (\Exception $exception) {
                $this->logger->reset($loggerIndentation);
                
                if ($muteDepth !== null) {
                    $this->logger->unMute($muteDepth);
                }

                throw $exception;
            }

            $this->logger->reset($loggerIndentation);
            
            if ($muteDepth !== null) {
                $this->logger->unMute($muteDepth);
            }

            if (!$result) {
                continue;
            }

            $appliedPatches[$source] = $info;
        }

        return $appliedPatches;
    }

    private function processPackagePatch(PackageInterface $package, array $info)
    {
        $source = $info[Patch::SOURCE];
        
        try {
            $installPath = $this->packageInfoResolver->getInstallPath($package, $info[Patch::CWD]);

            $this->dispatchEventForPackagePatch(Events::PRE_APPLY, $package, $info);
            
            $this->patchApplier->applyFile($info[Patch::PATH], $installPath, $info[Patch::CONFIG]);

            $this->dispatchEventForPackagePatch(Events::POST_APPLY, $package, $info);
            
            return true;
        } catch (\Exception $exception) {
            $this->logger->writeException($exception);

            $this->failureHandler->execute($exception->getMessage(), $source);
        }

        return false;
    }
    
    private function dispatchEventForPackagePatch($name, PackageInterface $package, array $patch)
    {
        $this->eventDispatcher->dispatch(
            Events::PRE_APPLY,
            new Event($name, $package, $patch[Patch::SOURCE], $patch[Patch::LABEL])
        );
    }
}
