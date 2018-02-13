<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
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
    private $targets;

    /**
     * @var array
     */
    private $filters;

    /**
     * @param \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser
     * @param array $targets
     * @param array $filters
     */
    public function __construct(
        \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser,
        array $targets,
        array $filters
    ) {
        $this->repositoryAnalyser = $repositoryAnalyser;
        $this->filters = $filters;
        $this->targets = $targets;
        
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function generate(PackageRepository $repository, array $patchesList) 
    {
        $patches = $patchesList;

        $targets = $this->targets;
        
        if ($this->filters) {
            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $this->filterUtils->composeRegex($this->filters, '/'),
                PatchDefinition::SOURCE
            );
        }

        $resets = $this->repositoryAnalyser->determinePackageResets($repository, $patches);

        if ($this->filters) {
            $targetedPatches = $this->patchListUtils->applyDefinitionFilter(
                $patchesList,
                $this->filterUtils->composeRegex(
                    $this->filterUtils->trimRules($this->filters), 
                    '/'
                ),
                PatchDefinition::SOURCE
            );

            $resets = array_intersect(
                $resets, 
                $this->patchListUtils->getAllTargets($targetedPatches)
            );
        }
        
        if ($targets) {
            $targetsFilter = $this->filterUtils->composeRegex($targets, '/');

            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $targetsFilter,
                PatchDefinition::TARGETS
            );
        }

        $resets = array_merge(
            $this->patchListUtils->getRelatedTargets($patchesList, $resets),
            $resets
        );
        
        return array($patches, $resets);
    }
}
