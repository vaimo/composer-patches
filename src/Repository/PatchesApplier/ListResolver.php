<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

class ListResolver
{
    /**
     * @var \Vaimo\ComposerPatches\Repository\Analyser
     */
    private $repositoryAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Repository\Analyser 
     */
    private $missingItemsAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @param \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser
     * @param \Vaimo\ComposerPatches\Repository\Analyser $missingItemsAnalyser
     */
    public function __construct(
        \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser,
        \Vaimo\ComposerPatches\Repository\Analyser $missingItemsAnalyser
    ) {
        $this->repositoryAnalyser = $repositoryAnalyser;
        $this->missingItemsAnalyser = $missingItemsAnalyser;
        
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }
    
    public function resolveRelatedQueue(PackageRepository $repository, array $patches, array $targets)
    {
        $relatedTargets = $this->patchListUtils->getRelatedTargets($patches, $targets);

        $relatedPatchesQueue = array();

        foreach ($relatedTargets as $key => $relatedPatches) {
            $relatedPatchesQueue[$key] = array_diff_key(
                isset($patches[$key]) ? $patches[$key] : array(),
                array_flip($relatedPatches)
            );
        }

        if ($relatedPatchesQueue) {
            $relatedPatchesQueue = array_intersect_key(
                $relatedPatchesQueue,
                $this->missingItemsAnalyser->determinePackageResets($repository, $relatedPatchesQueue)
            );
        }

        return $relatedPatchesQueue;
    }

    public function resolvePatchesResets(PackageRepository $repository, array $patches, array $filters)
    {
        $resets = $this->repositoryAnalyser->determinePackageResets($repository, $patches);

        $fullResets = array_keys(array_filter($resets, 'is_bool'));

        $resets = array_diff_key($resets, array_flip($fullResets));

        $targets = array_replace_recursive(
            $resets,
            $this->patchListUtils->createSimplifiedList(array_intersect_key($patches, $resets))
        );

        $resetPatches = $this->patchListUtils->createDetailedList($targets);

        foreach ($filters as $key => $filter) {
            $resetPatches = $this->patchListUtils->applyDefinitionFilter(
                $resetPatches,
                $this->filterUtils->composeRegex($this->filterUtils->trimRules($filter), '/'),
                $key
            );
        }

        return array_unique(
            array_merge($this->patchListUtils->getAllTargets($resetPatches), $fullResets)
        );
    }

    public function resolvePatchesQueue(array $patches, array $filters)
    {
        $patches = array_filter($patches);

        foreach ($filters as $key => $filter) {
            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $this->filterUtils->composeRegex($filter, '/'),
                $key
            );
        }

        return $patches;
    }
}
