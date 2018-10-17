<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Composer\Repository\WritableRepositoryInterface as Repository;
use Composer\Package\PackageInterface as Package;
use Vaimo\ComposerPatches\Patch\Definition as Patch;

class PatchesApplier
{
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
     * @var \Vaimo\ComposerPatches\Repository\State\Analyser
     */
    private $repositoryStateAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Repository\StateGenerator 
     */
    private $repositoryStateGenerator;

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
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageListUtils 
     */
    private $packageListUtils;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packageCollector
     * @param \Vaimo\ComposerPatches\Managers\RepositoryManager $repositoryManager
     * @param \Vaimo\ComposerPatches\Package\PatchApplier $patchApplier
     * @param \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator $queueGenerator
     * @param \Vaimo\ComposerPatches\Managers\PatcherStateManager $patcherStateManager
     * @param \Vaimo\ComposerPatches\Repository\State\Analyser $repositoryStateAnalyser
     * @param \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger $patchInfoLogger
     * @param \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packageCollector,
        \Vaimo\ComposerPatches\Managers\RepositoryManager $repositoryManager,
        \Vaimo\ComposerPatches\Package\PatchApplier $patchApplier,
        \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator $queueGenerator,
        \Vaimo\ComposerPatches\Managers\PatcherStateManager $patcherStateManager,
        \Vaimo\ComposerPatches\Repository\State\Analyser $repositoryStateAnalyser,
        \Vaimo\ComposerPatches\Package\PatchApplier\InfoLogger $patchInfoLogger,
        \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->packageCollector = $packageCollector;
        $this->repositoryManager = $repositoryManager;
        $this->packagePatchApplier = $patchApplier;
        $this->queueGenerator = $queueGenerator;
        $this->patcherStateManager = $patcherStateManager;
        $this->repositoryStateAnalyser = $repositoryStateAnalyser;
        $this->patchInfoLogger = $patchInfoLogger;
        $this->logger = $logger;

        $this->repositoryStateGenerator = new \Vaimo\ComposerPatches\Repository\StateGenerator(
            $this->packageCollector
        );

        $this->outputStrategy = $outputStrategy;
        
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
        $this->packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();
    }
    
    public function apply(Repository $repository, array $patches)
    {
        $packages = $this->packageCollector->collect($repository);

        $packagesUpdated = false;

        $repositoryState = $this->repositoryStateGenerator->generate($repository);
        
        $patchQueue = $this->queueGenerator->generateApplyQueue($patches, $repositoryState);
        
        $resetQueue = array_keys($patchQueue);

        $patchQueueFootprints = $this->patchListUtils->createSimplifiedList($patchQueue);

        $patchRemovals = array_replace(
            array_fill_keys(array_keys($packages), array()),
            $this->queueGenerator->generateRemovalQueue(
                array_replace($patches, $patchQueue), 
                $repositoryState
            )
        );
        
        foreach ($packages as $packageName => $package) {
            $hasPatches = !empty($patchQueue[$packageName]);

            if ($hasPatches) {
                $patchTargets = $this->patchListUtils->getAllTargets(array($patchQueue[$packageName]));
            } else {
                $patchTargets = array($packageName);
            }

            $itemsToReset = array_intersect($resetQueue, $patchTargets);

            $resetResult = array();

            foreach ($itemsToReset as $targetName) {
                $resetTarget = $packages[$targetName];

                $resetPatches = $this->packageUtils->resetAppliedPatches($resetTarget);
                $resetResult[$targetName] = is_array($resetPatches) ? $resetPatches : array();

                if (!$hasPatches && !isset($patchQueueFootprints[$targetName]) && $resetPatches) {
                    $this->logger->writeRaw(
                        'Resetting patched package <info>%s</info> (%s)',
                        array($targetName, count($resetResult[$targetName]))
                    );
                }

                $this->repositoryManager->resetPackage($repository, $resetTarget);

                $packagesUpdated = $packagesUpdated || (bool)$resetResult[$targetName];
            }

            $updates = $this->patchListUtils->generateKnownPatchFlagUpdates(
                $packageName, 
                $resetResult, 
                $patchQueueFootprints
            );
            
            $patchQueue = $this->patchListUtils->updateList($patchQueue, $updates);
            
            $resetQueue = array_diff($resetQueue, $patchTargets);

            if (!$hasPatches) {
                continue;
            }

            $changesMap = array();
            
            foreach ($patchTargets as $targetName) {
                $targetQueue = isset($patchQueueFootprints[$targetName])
                    ? $patchQueueFootprints[$targetName]
                    : array();

                if (!isset($packages[$targetName])) {
                    throw new \Vaimo\ComposerPatches\Exceptions\PackageNotFound(
                        sprintf(
                            'Unknown target "%s" encountered when checking patch changes for: %s',
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

            $changedTargets = array_keys(array_filter($changesMap));

            if (!$changedTargets) {
                continue;
            }

            $queuedPatches = array_filter(
                $patchQueue[$packageName],
                function ($data) use ($changedTargets) {
                    return array_intersect($data[Patch::TARGETS], $changedTargets);
                }
            );
            
            $muteDepth = null;
            
            if (!$this->shouldAllowOutput($queuedPatches, $patchRemovals[$packageName])) {
                $muteDepth = $this->logger->mute();
            } 
            
            try {
                $this->logger->writeRaw(
                    'Applying patches for <info>%s</info> (%s)',
                    array($packageName, count($queuedPatches))
                );
                
                if ($patchRemovals[$packageName]) {
                    $processIndentation = $this->logger->push('~');

                    foreach ($patchRemovals[$packageName] as $item) {
                        $this->patchInfoLogger->outputPatchInfo($item);
                    }

                    $this->logger->reset($processIndentation);
                }
                
                $this->processPatchesForPackage($repository, $package, $queuedPatches);
            } catch (\Exception $exception) {
                $this->logger->unMute();
                
                throw $exception;
            }

            $packagesUpdated = true;

            $this->logger->writeNewLine();

            if ($muteDepth !== null) {
                $this->logger->unMute($muteDepth);
            }
        }

        return $packagesUpdated;
    }
    
    private function processPatchesForPackage(Repository $repository, Package $package, $patchesQueue) 
    {
        $processIndentation = $this->logger->push('~');

        try {
            $appliedPatches = $this->packagePatchApplier->applyPatches($package, $patchesQueue);

            $this->patcherStateManager->registerAppliedPatches($repository, $appliedPatches);

            $this->logger->reset($processIndentation);
        } catch (\Vaimo\ComposerPatches\Exceptions\PatchFailureException $exception) {
            $failedPath = $exception->getFailedPatchPath();

            $paths = array_keys($patchesQueue);
            $appliedPaths = array_slice($paths, 0, array_search($failedPath, $paths));
            $appliedPatches = array_intersect_key($patchesQueue, array_flip($appliedPaths));

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
