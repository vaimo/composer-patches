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
