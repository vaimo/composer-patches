<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package\PatchApplier;

use Composer\Package\PackageInterface;

use Vaimo\ComposerPatches\Patch\Event;
use Vaimo\ComposerPatches\Events;
use Vaimo\ComposerPatches\Patch\Definition as Patch;

class ItemProcessor
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
    private $fileApplier;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @param \Composer\EventDispatcher\EventDispatcher $eventDispatcher
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     * @param \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler
     * @param \Vaimo\ComposerPatches\Patch\File\Applier $fileApplier
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Composer\EventDispatcher\EventDispatcher $eventDispatcher,
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver,
        \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler,
        \Vaimo\ComposerPatches\Patch\File\Applier $fileApplier,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->packageInfoResolver = $packageInfoResolver;
        $this->failureHandler = $failureHandler;
        $this->fileApplier = $fileApplier;
        $this->logger = $logger;
    }
    
    public function processFileInfo(PackageInterface $package, array $info)
    {
        $source = $info[Patch::SOURCE];

        try {
            $installPath = $this->packageInfoResolver->getInstallPath($package, $info[Patch::CWD]);

            $this->dispatchEventForPackagePatch(Events::PRE_APPLY, $package, $info);

            $this->fileApplier->applyFile($info[Patch::PATH], $installPath, $info[Patch::CONFIG]);

            $this->dispatchEventForPackagePatch(Events::POST_APPLY, $package, $info);

            return true;
        } catch (\Exception $exception) {
            $this->logger->writeException($exception);

            $this->failureHandler->execute($exception, $source);
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
