<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\ComposerPatches\Composer\ResetOperation;
use Vaimo\ComposerPatches\Repositories\PatchesRepository;
use Vaimo\ComposerPatches\Composer\OutputUtils;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PatchesApplier
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Vaimo\ComposerPatches\Package\PatchApplier
     */
    private $patchApplier;

    /**
     * @var \Vaimo\ComposerPatches\Repository\Analyser
     */
    private $repositoryAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Managers\PatcherStateManager
     */
    private $patcherStateManager;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Vaimo\ComposerPatches\Package\PatchApplier $patchApplier
     * @param \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser
     * @param \Vaimo\ComposerPatches\Managers\PatcherStateManager $patcherStateManager
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        \Vaimo\ComposerPatches\Package\PatchApplier $patchApplier,
        \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser,
        \Vaimo\ComposerPatches\Managers\PatcherStateManager $patcherStateManager,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->installationManager = $installationManager;
        $this->patchApplier = $patchApplier;
        $this->repositoryAnalyser = $repositoryAnalyser;
        $this->patcherStateManager = $patcherStateManager;

        $this->logger = $logger;

        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function apply(PatchesRepository $repository, array $targets = array(), array $filters = array())
    {
        $packages = $repository->getTargets();

        $packagesUpdated = false;

        $this->logger->write('info', 'Processing patches configuration');

        $patches = $repository->getPatches();

        /**
         * Resolve targets based on file filter, filter down patches list, leave only packages that have
         * matching file
         */
        if ($filters) {
            if ($composedFilter = $this->filterUtils->composeRegex($filters, '/')) {
                $patches = $this->patchListUtils->applyDefinitionFilter(
                    $patches,
                    $composedFilter,
                    PatchDefinition::SOURCE
                );

                if (!$targets) {
                    $targets = $this->patchListUtils->getAllTargets($patches);
                }
            }
        }

        /**
         * Determine packages to reset based on applied patches and patches that are defined.
         */
        $resetQueue = $this->repositoryAnalyser->determinePackageResets($repository->getSource(), $patches);

        /**
         * Apply targets filter. Leave only patches that have a match on their targets configuration
         */
        if ($targets) {
            if ($targetsFilter = $this->filterUtils->composeRegex($targets, '/')) {
                $patches = $this->patchListUtils->applyDefinitionFilter(
                    $patches,
                    $targetsFilter,
                    PatchDefinition::TARGETS
                );

                $subset = array_merge(
                    !$patches ? array_values(preg_grep($targetsFilter, array_keys($packages))) : array(),
                    $this->patchListUtils->getAllTargets($patches)
                );

                $resetQueue = array_intersect($resetQueue, $subset);
            }
        }

        /**
         * Apply the patches
         */
        $resetQueue = array_merge(
            $this->repositoryAnalyser->determineRelatedTargets($patches, $resetQueue),
            $resetQueue
        );

        $patchQueue = $this->patchListUtils->createSimplifiedList($patches);

        $loggerIndentation = $this->logger->push('-');

        foreach ($packages as $packageName => $package) {
            $hasPatches = !empty($patches[$packageName]);

            if ($hasPatches) {
                $patchTargets = array();

                foreach ($patches[$packageName] as $patch) {
                    $patchTargets = array_merge($patchTargets, $patch[PatchDefinition::TARGETS]);
                }

                $patchTargets = array_unique($patchTargets);
            } else {
                $patchTargets = array($packageName);
            }

            $itemsToReset = array_intersect($resetQueue, $patchTargets);

            foreach ($itemsToReset as $targetName) {
                $resetTarget = $packages[$targetName];

                $resetPatches = $this->packageUtils->resetAppliedPatches($resetTarget);

                if (!$hasPatches && !isset($patchQueue[$targetName])) {
                    $this->logger->writeRaw(
                        'Resetting patched package <info>%s</info> (%s)', 
                        array($targetName, count($resetPatches))
                    );
                }

                /** @var \Composer\IO\ConsoleIO $output */
                $output = $this->logger->getOutputInstance();

                $verbosityLevel = OutputUtils::resetVerbosity($output, OutputInterface::VERBOSITY_QUIET);


                try {
                    $operation = new ResetOperation(
                        $resetTarget,
                        'Package reset due to changes in patches configuration'
                    );

                    $this->installationManager->install($repository->getSource(), $operation);

//                    \Composer\Script\ScriptEvents::POST_INSTALL_CMD
//                    $resetTarget->getDeployStrategy()->deploy();
                } finally {
                    OutputUtils::resetVerbosity($output, $verbosityLevel);
                }
                
                if (isset($patchQueue[$targetName])) {
                    $silentPatches = array_intersect_assoc($patchQueue[$targetName], $resetPatches);

                    foreach (array_keys($silentPatches) as $silentPatchPath) {
                        $patches[$targetName][$silentPatchPath][PatchDefinition::CHANGED] = false;
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
                $targetQueue = isset($patchQueue[$targetName])
                    ? $patchQueue[$targetName]
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
                array($packageName, count($patches[$packageName]))
            );

            $packagePatchesQueue = $patches[$packageName];
            $packageRepository = $repository->getSource();

            $subProcessIndentation = $this->logger->push('~');

            try {
                $appliedPatches = $this->patchApplier->applyPatches($package, $packagePatchesQueue);

                $this->patcherStateManager->registerAppliedPatches($packageRepository, $appliedPatches);

                $this->logger->reset($subProcessIndentation);
            } catch (\Vaimo\ComposerPatches\Exceptions\PatchFailureException $exception) {
                $failedPath = $exception->getFailedPatchPath();

                $paths = array_keys($packagePatchesQueue);
                $appliedPaths = array_slice($paths, 0, array_search($failedPath, $paths));
                $appliedPatches = array_intersect_key($packagePatchesQueue, array_flip($appliedPaths));

                $this->patcherStateManager->registerAppliedPatches($packageRepository, $appliedPatches);

                $this->patchListUtils->sanitizeFileSystem($patches);

                $this->logger->reset($loggerIndentation);

                $repository->write();

                throw $exception;
            }

            $this->logger->writeNewLine();
        }

        $this->logger->reset($loggerIndentation);

        $this->patchListUtils->sanitizeFileSystem($patches);

        if (!$packagesUpdated) {
            $this->logger->writeRaw('Nothing to patch');
        } else {
            $this->logger->write('info', 'Writing patch info to install file');
        }

        $repository->write();
    }
}
