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
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils 
     */
    private $patchListUtils;

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
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();

        $this->stateLabels = array(
            PatchDefinition::STATUS_NEW => 'NEW',
            PatchDefinition::STATUS_CHANGED => 'CHANGED'
        );
    }

    private function resolveOutputTriggers(array $patchQueue)
    {
        $hasFilterMatches = (bool)$this->patchListUtils->applyDefinitionFilter(
            $patchQueue,
            true,
            PatchDefinition::STATUS_MATCH
        );

        if ($hasFilterMatches) {
            return array(
                PatchDefinition::STATUS_MATCH
            );
        }

        return array(
            PatchDefinition::STATUS_NEW,
            PatchDefinition::STATUS_CHANGED
        );
    }

    private function shouldNotify(array $patchesQueue, array $outputTriggers)
    {
        $muteTriggersMatcher = array_flip($outputTriggers);

        return (bool)array_filter($patchesQueue, function (array $patch) use ($muteTriggersMatcher) {
            return array_filter(
                array_intersect_key($patch, $muteTriggersMatcher)
            );
        });
    }

    public function applyPatches(PackageInterface $package, array $patchesQueue)
    {
        $appliedPatches = array();

        $outputTriggers = $this->resolveOutputTriggers([$patchesQueue]);
        
        foreach ($patchesQueue as $source => $info) {
            $muteDepth = !$this->shouldNotify([$info], $outputTriggers) ? $this->logger->mute() : null;
            
            if ($info[PatchDefinition::STATUS_LABEL]) {
                $labelMatches = array($info[PatchDefinition::STATUS_LABEL]);
            } else {
                $labelMatches = array_intersect_key($this->stateLabels, array_filter($info));
            }
            
            $this->logger->writeRaw(
                '<info>%s</info>: %s%s',
                array(
                    $info[PatchDefinition::OWNER],
                    $source,
                    $labelMatches ? vsprintf(' [<info>%s</info>]', $labelMatches) : ''
                )
            );

            $loggerIndentation = $this->logger->push();

            if (trim($info[PatchDefinition::LABEL])) {
                $labelLines = explode(PHP_EOL, $info[PatchDefinition::LABEL]);

                $labelReference = (isset($info[PatchDefinition::LINK]) && $info[PatchDefinition::LINK])
                    ? $info[PatchDefinition::LINK]
                    : ((isset($info[PatchDefinition::ISSUE]) && $info[PatchDefinition::ISSUE])
                        ? $info[PatchDefinition::ISSUE]
                        : false
                    );

                if ($labelReference) {
                    if (count($labelLines) == 1) {
                        $labelLines = array(
                            sprintf('%s (<fg=default;options=underscore>%s</>)',reset($labelLines), $labelReference)
                        );
                    } else {
                        $labelLines[] = sprintf('reference: <fg=default;options=underscore>%s</>', $labelReference);
                    }
                }

                foreach ($labelLines as $line) {
                    $this->logger->write('comment', $line);
                }
            }

            try {
                $result = $this->processPackagePatch($package, $source, $info);
            } catch (\Exception $e) {
                $this->logger->reset($loggerIndentation);
                
                if ($muteDepth !== null) {
                    $this->logger->unMute($muteDepth);
                }

                throw $e;
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

    private function processPackagePatch(PackageInterface $package, $source, $info)
    {
        try {
            $this->eventDispatcher->dispatch(
                Events::PRE_APPLY,
                new Event(Events::PRE_APPLY, $package, $source, $info[PatchDefinition::LABEL])
            );

            $this->patchApplier->applyFile(
                $info[PatchDefinition::PATH],
                $this->getInstallPath($package, $info['cwd']),
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

    private function getInstallPath(PackageInterface $package, $resolveMode)
    {
        $name = $package->getName();
        $key = $name . '|' . $resolveMode;

        if (!isset($this->installPathCache[$key])) {
            switch ($resolveMode) {
                case PatchDefinition::CWD_VENDOR:
                    $this->installPathCache[$key] = $this->vendorRoot;
                    break;
                case PatchDefinition::CWD_PROJECT:
                    $this->installPathCache[$key] = getcwd();
                    break;
                case PatchDefinition::CWD_INSTALL:
                default:
                    if ($package instanceof \Composer\Package\RootPackage) {
                        $this->installPathCache[$key] = $this->vendorRoot;
                    } else {
                        $this->installPathCache[$key] = $this->packageInfoResolver->getSourcePath($package);
                    }

                    break;
            }
        }

        return $this->installPathCache[$key];
    }
}
