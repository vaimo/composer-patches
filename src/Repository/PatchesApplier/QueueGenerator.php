<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

class QueueGenerator
{
    /**
     * @var \Vaimo\ComposerPatches\Repository\Analyser
     */
    private $repositoryAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @var array
     */
    private $filters;

    /**
     * @param \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser
     * @param array $filters
     */
    public function __construct(
        \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser,
        array $filters
    ) {
        $this->repositoryAnalyser = $repositoryAnalyser;
        $this->filters = $filters;

        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function generate(PackageRepository $repository, array $patchesList)
    {
        $patches = $this->resolvePatchesQueue($patchesList);
        $resets = $this->resolvePatchesResets($repository, $patches);

        return array(
            $patches,
            array_unique(
                array_merge($this->patchListUtils->getRelatedTargets($patchesList, $resets), $resets)
            )
        );
    }

    private function resolvePatchesResets(PackageRepository $repository, array $patches)
    {
        $resets = $this->repositoryAnalyser->determinePackageResets($repository, $patches);

        $targets = array_replace_recursive(
            $resets,
            $this->patchListUtils->createSimplifiedList(array_intersect_key($patches, $resets))
        );

        $resetPatches = $this->patchListUtils->createDetailedList($targets);

        foreach ($this->filters as $key => $filter) {
            $resetPatches = $this->patchListUtils->applyDefinitionFilter(
                $resetPatches,
                $this->filterUtils->composeRegex($this->filterUtils->trimRules($filter), '/'),
                $key
            );
        }

        return $this->patchListUtils->getAllTargets($resetPatches);
    }

    private function resolvePatchesQueue(array $patches)
    {
        $patches = array_filter($patches);

        foreach ($this->filters as $key => $filter) {
            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $this->filterUtils->composeRegex($filter, '/'),
                $key
            );
        }

        return $patches;
    }
}
