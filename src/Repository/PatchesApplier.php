<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\ComposerPatches\Composer\ResetOperation;
use Vaimo\ComposerPatches\Composer\OutputUtils;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Composer\Repository\WritableRepositoryInterface as PackageRepository;

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
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packageCollector
     * @param \Vaimo\ComposerPatches\Managers\RepositoryManager $repositoryManager
     * @param \Vaimo\ComposerPatches\Package\PatchApplier $patchApplier
     * @param \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator $queueGenerator
     * @param \Vaimo\ComposerPatches\Managers\PatcherStateManager $patcherStateManager
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packageCollector,
        \Vaimo\ComposerPatches\Managers\RepositoryManager $repositoryManager,
        \Vaimo\ComposerPatches\Package\PatchApplier $patchApplier,
        \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator $queueGenerator,
        \Vaimo\ComposerPatches\Managers\PatcherStateManager $patcherStateManager,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->packageCollector = $packageCollector;
        $this->repositoryManager = $repositoryManager;
        $this->packagePatchApplier = $patchApplier;
        $this->queueGenerator = $queueGenerator;
        $this->patcherStateManager = $patcherStateManager;

        $this->logger = $logger;

        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function apply(PackageRepository $repository, array $applyQueue) 
    {
        $packages = $this->packageCollector->collect($repository);

        $packagesUpdated = false;

        $this->logger->write('info', 'Processing patches configuration');

        list ($applyQueue, $resetQueue) = $this->queueGenerator->generate($repository, $applyQueue);

        $flatPatchesList = $this->patchListUtils->createSimplifiedList($applyQueue);

        $loggerIndentation = $this->logger->push('-');

        foreach ($packages as $packageName => $package) {
            $hasPatches = !empty($applyQueue[$packageName]);

            if ($hasPatches) {
                $patchTargets = $this->patchListUtils->getAllTargets(array($applyQueue[$packageName]));
            } else {
                $patchTargets = array($packageName);
            }

            $itemsToReset = array_intersect($resetQueue, $patchTargets);

            foreach ($itemsToReset as $targetName) {
                $resetTarget = $packages[$targetName];

                $resetPatches = $this->packageUtils->resetAppliedPatches($resetTarget);

                if (!$hasPatches && !isset($flatPatchesList[$targetName])) {
                    $this->logger->writeRaw(
                        'Resetting patched package <info>%s</info> (%s)',
                        array($targetName, count($resetPatches))
                    );
                }

                $this->repositoryManager->resetPackage($repository, $resetTarget);

                if (isset($flatPatchesList[$targetName])) {
                    $knownPatches = array_intersect_assoc($flatPatchesList[$targetName], $resetPatches);

                    foreach (array_keys($knownPatches) as $silentPatchPath) {
                        $applyQueue[$targetName][$silentPatchPath][PatchDefinition::CHANGED] = false;
                    };
                }

                $packagesUpdated = (bool)$resetPatches;
            }

            $resetQueue = array_diff($resetQueue, $patchTargets);

            if (!$hasPatches) {
                continue;
            }

            $hasPatchChanges = false;
            foreach ($patchTargets as $targetName) {
                $targetQueue = isset($flatPatchesList[$targetName])
                    ? $flatPatchesList[$targetName]
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

                $target = $packages[$targetName];

                if (!$hasPatchChanges = $this->packageUtils->hasPatchChanges($target, $targetQueue)) {
                    continue;
                }
            }

            if (!$hasPatchChanges) {
                continue;
            }

            $packagesUpdated = true;

            $this->logger->writeRaw(
                'Applying patches for <info>%s</info> (%s)',
                array($packageName, count($applyQueue[$packageName]))
            );

            $packagePatchesQueue = $applyQueue[$packageName];

            $subProcessIndentation = $this->logger->push('~');

            try {
                $appliedPatches = $this->packagePatchApplier->applyPatches($package, $packagePatchesQueue);

                $this->patcherStateManager->registerAppliedPatches($repository, $appliedPatches);

                $this->logger->reset($subProcessIndentation);
            } catch (\Vaimo\ComposerPatches\Exceptions\PatchFailureException $exception) {
                $failedPath = $exception->getFailedPatchPath();

                $paths = array_keys($packagePatchesQueue);
                $appliedPaths = array_slice($paths, 0, array_search($failedPath, $paths));
                $appliedPatches = array_intersect_key($packagePatchesQueue, array_flip($appliedPaths));

                $this->patcherStateManager->registerAppliedPatches($repository, $appliedPatches);

                $this->patchListUtils->sanitizeFileSystem($applyQueue);

                $this->logger->reset($loggerIndentation);

                $repository->write();

                throw $exception;
            }

            $this->logger->writeNewLine();
        }

        $this->logger->reset($loggerIndentation);

        $this->patchListUtils->sanitizeFileSystem($applyQueue);

        if (!$packagesUpdated) {
            $this->logger->writeRaw('Nothing to patch');
        } else {
            $this->logger->write('info', 'Writing patch info to install file');
        }

        $repository->write();
    }
}
