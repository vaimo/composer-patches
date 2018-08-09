<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;
use Composer\Package\PackageInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\ComposerPatches\Composer\OutputUtils;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

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
     * \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

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

    public function apply(PackageRepository $repository, array $patches)
    {
        $packages = $this->packageCollector->collect($repository);

        $packagesUpdated = false;

        list ($patchList, $resetList) = $this->queueGenerator->generate($repository, $patches);

        $infoList = $this->patchListUtils->createSimplifiedList($patchList);
        $output = $this->logger->getOutputInstance();

        foreach ($packages as $packageName => $package) {
            $hasPatches = !empty($patchList[$packageName]);

            if ($hasPatches) {
                $patchTargets = $this->patchListUtils->getAllTargets(array($patchList[$packageName]));
            } else {
                $patchTargets = array($packageName);
            }

            $itemsToReset = array_intersect($resetList, $patchTargets);

            $resetResult = array();

            foreach ($itemsToReset as $targetName) {
                $resetTarget = $packages[$targetName];

                $resetPatches = $this->packageUtils->resetAppliedPatches($resetTarget);
                $resetResult[$targetName] = is_array($resetPatches) ? $resetPatches : array();

                if (!$hasPatches && !isset($infoList[$targetName]) && $resetPatches) {
                    $this->logger->writeRaw(
                        'Resetting patched package <info>%s</info> (%s)',
                        array($targetName, count($resetResult[$targetName]))
                    );
                }

                $this->repositoryManager->resetPackage($repository, $resetTarget);
                $packagesUpdated = $packagesUpdated || (bool)$resetResult[$targetName];
            }

            $patchList = $this->patchListUtils->updateList(
                $patchList,
                $this->patchListUtils->generateKnownPatchFlagUpdates($packageName, $resetResult, $infoList)
            );

            $resetList = array_diff($resetList, $patchTargets);

            if (!$hasPatches) {
                continue;
            }

            $hasPatchChanges = false;
            foreach ($patchTargets as $targetName) {
                $targetQueue = isset($infoList[$targetName])
                    ? $infoList[$targetName]
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

            $this->processPatchesForPackage($package, $patchList[$packageName], $repository);

            $packagesUpdated = true;

            $this->logger->writeNewLine();
        }

        return $packagesUpdated;
    }

    private function processPatchesForPackage(PackageInterface $package, $patchesQueue, $repository)
    {
        $this->logger->writeRaw(
            'Applying patches for <info>%s</info> (%s)',
            array($package->getName(), count($patchesQueue))
        );

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
}
