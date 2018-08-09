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
     * @var array
     */
    private $stateLabels;

    /**
     * @var array
     */
    private $installPathCache = array();

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

        $this->stateLabels = array(
            PatchDefinition::NEW => 'NEW',
            PatchDefinition::CHANGED => 'CHANGED'
        );
    }

    public function applyPatches(PackageInterface $package, array $patchesQueue)
    {
        $appliedPatches = array();

        foreach ($patchesQueue as $source => $info) {
            $labelMatches = array_intersect_key($this->stateLabels, array_filter($info));

            $this->logger->writeRaw(
                '<info>%s</info>: %s%s',
                array(
                    $info[PatchDefinition::OWNER],
                    $source,
                    $labelMatches ? vsprintf(' [<info>%s</info>]', $labelMatches) : ''
                )
            );

            $loggerIndentation = $this->logger->push();

            $this->logger->writeRaw('<comment>%s</comment>', array($info[PatchDefinition::LABEL]));

            try {
                $result = $this->processPackagePatch($package, $source, $info);
            } catch (\Exception $e) {
                $this->logger->reset($loggerIndentation);

                throw $e;
            }

            $this->logger->reset($loggerIndentation);

            if (!$result) {
                continue;
            }

            $appliedPatches[$source] = $info;
        }

        return $appliedPatches;
    }

    private function processPackagePatch(PackageInterface $package, $source, $info)
    {
        try {
            $this->eventDispatcher->dispatch(
                Events::PRE_APPLY,
                new Event(Events::PRE_APPLY, $package, $source, $info[PatchDefinition::LABEL])
            );

            $this->patchApplier->applyFile(
                $info[PatchDefinition::PATH],
                $this->getInstallPath($package),
                $info[PatchDefinition::CONFIG]
            );

            $this->eventDispatcher->dispatch(
                Events::POST_APPLY,
                new Event(Events::POST_APPLY, $package, $source, $info[PatchDefinition::LABEL])
            );

            return true;
        } catch (\Exception $exception) {
            $this->logger->writeException($exception);

            $this->failureHandler->execute($exception->getMessage(), $source);
        }

        return false;
    }

    private function getInstallPath(PackageInterface $package)
    {
        $name = $package->getName();

        if (!isset($this->installPathCache[$name])) {
            if ($package instanceof \Composer\Package\RootPackage) {
                $this->installPathCache[$name] = $this->vendorRoot;
            } else {
                $this->installPathCache[$name] = $this->packageInfoResolver->getSourcePath($package);
            }
        }

        return $this->installPathCache[$name];
    }
}
