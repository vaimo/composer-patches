<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Composer\Repository\WritableRepositoryInterface as Repository;
use Composer\Package\PackageInterface as Package;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PatchesApplier
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Vaimo\ComposerPatches\Package\Collector
     */
    private $packageCollector;

    /**
     * @var \Vaimo\ComposerPatches\Managers\RepositoryManager
     */
    private $repositoryManager;

    /**
     * @var \Vaimo\ComposerPatches\Package\PatchApplier
     */
    private $packagePatchApplier;

    /**
     * @var \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator
     */
    private $queueGenerator;

    /**
     * @var \Vaimo\ComposerPatches\Managers\PatcherStateManager
     */
    private $patcherStateManager;

    /**
     * @var \Vaimo\ComposerPatches\Repository\StateGenerator
     */
    private $repoStateGenerator;

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
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Analyser
     */
    private $patchListAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer
     */
    private $patchListTransformer;

    /**
     * @var \Vaimo\ComposerPatches\Package\PatchApplier\StatusConfig
     */
    private $statusConfig;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\DataUtils
     */
    private $dataUtils;

    /**
     * @var \Vaimo\ComposerPatches\Console\OutputGenerator
     */
    private $outputGenerator;

    /**
     * @var \Vaimo\ComposerPatches\Compatibility\Executor
     */
    private $compExecutor;

    public function __construct(
        \Composer\Composer $composer,
        \Vaimo\ComposerPatches\Package\Collector $packageCollector,
        \Vaimo\ComposerPatches\Managers\RepositoryManager $repositoryManager,
        \Vaimo\ComposerPatches\Package\PatchApplier $patchApplier,
        \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator $queueGenerator,
        \Vaimo\ComposerPatches\Managers\PatcherStateManager $patcherStateManager,
        \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger $patchInfoLogger,
        \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->composer = $composer;
        $this->packageCollector = $packageCollector;
        $this->repositoryManager = $repositoryManager;
        $this->packagePatchApplier = $patchApplier;
        $this->queueGenerator = $queueGenerator;
        $this->patcherStateManager = $patcherStateManager;
        $this->patchInfoLogger = $patchInfoLogger;
        $this->outputStrategy = $outputStrategy;
        $this->logger = $logger;

        $this->repoStateGenerator = new \Vaimo\ComposerPatches\Repository\StateGenerator(
            $this->packageCollector
        );

        $this->outputGenerator = new \Vaimo\ComposerPatches\Console\OutputGenerator($logger);

        $this->patchListAnalyser = new \Vaimo\ComposerPatches\Patch\DefinitionList\Analyser();
        $this->patchListTransformer = new \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer();
        $this->statusConfig = new \Vaimo\ComposerPatches\Package\PatchApplier\StatusConfig();
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
        $this->dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();
        $this->compExecutor = new \Vaimo\ComposerPatches\Compatibility\Executor();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param Repository $repository
     * @param array $patches
     * @return bool
     * @throws \Vaimo\ComposerPatches\Exceptions\PackageNotFound
     * @throws \Vaimo\ComposerPatches\Exceptions\PackageResetException
     * @throws \Vaimo\ComposerPatches\Exceptions\PatchFailureException
     */
    public function apply(Repository $repository, array $patches)
    {
        $packages = $this->packageCollector->collect($repository);

        $packagesUpdated = false;

        $repositoryState = $this->repoStateGenerator->generate($repository);

        $applyQueue = $this->queueGenerator->generateApplyQueue($patches, $repositoryState);
        $removeQueue = $this->queueGenerator->generateRemovalQueue($applyQueue, $repositoryState);
        $resetQueue = $this->queueGenerator->generateResetQueue($applyQueue);

        $applyQueue = array_map('array_filter', $applyQueue);

        $patchQueueFootprints = $this->patchListTransformer->createSimplifiedList($applyQueue);

        $labels = array_diff_key(
            $this->statusConfig->getLabels(),
            array(Patch::STATUS_UNKNOWN => true)
        );

        $applyQueue = $this->updateStatusLabels($applyQueue, $labels);
        $removeQueue = $this->updateStatusLabels($removeQueue, $labels);

        foreach ($packages as $packageName => $package) {
            $hasPatches = !empty($applyQueue[$packageName]);

            $patchTargets = $hasPatches ?
                $this->patchListAnalyser->getAllTargets(array($applyQueue[$packageName]))
                : array($packageName);

            $itemsToReset = $this->dataUtils->extractItems($resetQueue, $patchTargets);

            $resetResult = array();
            $resets = array();

            foreach ($itemsToReset as $targetName) {
                $resetTarget = $packages[$targetName];

                $resetPatches = $this->packageUtils->resetAppliedPatches($resetTarget);

                $resetResult[$targetName] = is_array($resetPatches)
                    ? $resetPatches
                    : array();

                if (!$hasPatches && $resetPatches && !isset($patchQueueFootprints[$targetName])) {
                    $this->logger->writeRaw(
                        'Resetting patches for <info>%s</info> (%s)',
                        array($targetName, count($resetResult[$targetName]))
                    );
                }

                $resets[] = $this->repositoryManager->resetPackage($repository, $resetTarget);
            }

            $this->compExecutor->waitForCompletion($this->composer, $resets);

            $packagesUpdated = $packagesUpdated || (bool)array_filter($resetResult);

            if (!$hasPatches) {
                continue;
            }

            $changedTargets = $this->resolveChangedTargets($packages, $patchTargets, $patchQueueFootprints);

            if (empty($changedTargets)) {
                continue;
            }

            $queuedPatches = array_filter(
                $applyQueue[$packageName],
                function ($data) use ($changedTargets) {
                    return array_intersect($data[Patch::TARGETS], $changedTargets);
                }
            );

            $this->updatePackage(
                $package,
                $repository,
                $queuedPatches,
                $this->dataUtils->extractValue($removeQueue, $packageName, array())
            );

            $packagesUpdated = true;
        }

        return $packagesUpdated;
    }

    private function updatePackage(Package $package, Repository $repository, array $additions, array $removals)
    {
        $muteDepth = null;

        $packageName = $package->getName();

        if (!$this->shouldAllowOutput($additions, $removals)) {
            $muteDepth = $this->logger->mute();
        }

        $this->logger->writeRaw(
            'Applying patches for <info>%s</info> (%s)',
            array($packageName, count($additions))
        );

        $this->processQueues($package, $repository, $additions, $removals);

        $this->logger->writeNewLine();

        if ($muteDepth !== null) {
            $this->logger->unMute($muteDepth);
        }
    }

    private function updateStatusLabels(array $queue, array $labels)
    {
        foreach ($queue as $target => $group) {
            foreach ($group as $path => $item) {
                $status = isset($item[Patch::STATUS])
                    ? $item[Patch::STATUS]
                    : Patch::STATUS_UNKNOWN;

                if (!isset($labels[$status])) {
                    continue;
                }

                $queue[$target][$path][Patch::STATUS_LABEL] = $labels[$status];
            }
        }

        return $queue;
    }

    private function processQueues(Package $package, Repository $repository, $additions, $removals)
    {
        try {
            if ($removals) {
                $processIndentation = $this->logger->push('~');

                foreach ($removals as $item) {
                    $this->patchInfoLogger->outputPatchInfo($item);
                }

                $this->logger->reset($processIndentation);
            }

            $this->processPatchesForPackage($repository, $package, $additions);
        } catch (\Exception $exception) {
            $this->logger->unMute();

            throw $exception;
        }
    }

    private function resolveChangedTargets(array $packages, array $patchTargets, array $patchFootprints)
    {
        $changesMap = array();

        foreach ($patchTargets as $targetName) {
            $targetQueue = array();

            if (isset($patchFootprints[$targetName])) {
                $targetQueue = $patchFootprints[$targetName];
            }

            if (!isset($packages[$targetName])) {
                throw new \Vaimo\ComposerPatches\Exceptions\PackageNotFound(
                    sprintf(
                        'Unknown target "%s" found when checking patch changes for: %s',
                        $targetName,
                        implode(',', array_keys($targetQueue))
                    )
                );
            }

            $changesMap[$targetName] = $this->packageUtils->hasPatchChanges(
                $packages[$targetName],
                $targetQueue
            );
        }

        return array_keys(array_filter($changesMap));
    }

    private function processPatchesForPackage(Repository $repository, Package $package, array $patchesQueue)
    {
        $processIndentation = $this->logger->push('~');

        try {
            $appliedPatches = $this->packagePatchApplier->applyPatches($package, $patchesQueue);

            $this->patcherStateManager->registerAppliedPatches($repository, $appliedPatches);

            $this->logger->reset($processIndentation);
        } catch (\Vaimo\ComposerPatches\Exceptions\PatchFailureException $exception) {
            $this->logger->push();

            $this->logger->write('error', $exception->getMessage());

            $previousError = $exception->getPrevious();

            if ($previousError) {
                $this->outputGenerator->generateForException($previousError);
            }

            $failedPath = $exception->getFailedPatchPath();

            $paths = array_keys($patchesQueue);

            $failureIndex = array_search($failedPath, $paths, true);

            $appliedPatches = array();

            if (is_int($failureIndex)) {
                $appliedPaths = array_slice($paths, 0, $failureIndex);

                $appliedPatches = array_intersect_key(
                    $patchesQueue,
                    array_flip($appliedPaths)
                );
            }

            $this->patcherStateManager->registerAppliedPatches($repository, $appliedPatches);

            throw $exception;
        }
    }

    private function shouldAllowOutput(array $patches, array $removals)
    {
        return $this->outputStrategy->shouldAllowForPatches($patches)
            || $this->outputStrategy->shouldAllowForPatches($removals);
    }
}
