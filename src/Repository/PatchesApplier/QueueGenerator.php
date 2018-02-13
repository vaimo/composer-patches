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
        $patches = array_filter($patchesList);
        $resets = $this->repositoryAnalyser->determinePackageResets($repository, $patches);
        
        foreach ($this->filters as $key => $filter) {
            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $this->filterUtils->composeRegex($filter, '/'),
                $key
            );

            $targetedPatches = $this->patchListUtils->applyDefinitionFilter(
                $patchesList,
                $this->filterUtils->composeRegex($this->filterUtils->trimRules($filter), '/'),
                $key
            );

            $resets = array_intersect(
                $this->repositoryAnalyser->determinePackageResets($repository, $patches),
                $this->patchListUtils->getAllTargets($targetedPatches)
            );
        }
        
        return array(
            $patches,
            array_merge($this->patchListUtils->getRelatedTargets($patchesList, $resets), $resets)
        );
    }
}
