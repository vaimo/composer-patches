<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\Package\PackageInterface;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class PatchApplier
{
    /**
     * @var \Vaimo\ComposerPatches\Package\PatchApplier\ItemProcessor
     */
    private $fileProcessor;

    /**
     * @var \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger
     */
    private $detailsLogger;

    /**
     * @var \Vaimo\ComposerPatches\Strategies\OutputStrategy
     */
    private $outputStrategy;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @param \Vaimo\ComposerPatches\Package\PatchApplier\ItemProcessor $fileProcessor
     * @param \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger $detailsLogger
     * @param \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\PatchApplier\ItemProcessor $fileProcessor,
        \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger $detailsLogger,
        \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->fileProcessor = $fileProcessor;
        $this->detailsLogger = $detailsLogger;
        $this->outputStrategy = $outputStrategy;
        $this->logger = $logger;
    }

    /**
     * @param PackageInterface $package
     * @param array $patchesQueue
     * @return array
     * @throws \Exception
     * @throws \Vaimo\ComposerPatches\Exceptions\PatchFailureException
     */
    public function applyPatches(PackageInterface $package, array $patchesQueue)
    {
        $appliedPatches = array();

        foreach ($patchesQueue as $source => $info) {
            $muteDepth = !$this->outputStrategy->shouldAllowForPatches(array($info))
                ? $this->logger->mute()
                : null;

            $patchInfo = array_replace($info, array(Patch::SOURCE => $source));

            $this->detailsLogger->outputPatchSource($patchInfo);

            $loggerIndentation = $this->logger->push();

            $this->detailsLogger->outputPatchDescription($patchInfo);

            try {
                $result = $this->fileProcessor->processFileInfo($package, $patchInfo);
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
}
