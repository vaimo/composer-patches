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

    public function generate(PackageRepository $repository, array $patches) 
    {
        $names = array_keys($patches);

        $targets = $this->targets;
        
        if ($this->filters) {
            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $this->filterUtils->composeRegex($this->filters, '/'),
                PatchDefinition::SOURCE
            );

            if (!$targets) {
                $targets = $this->patchListUtils->getAllTargets($patches);
            }
        }

        $resets = $this->repositoryAnalyser->determinePackageResets($repository, $patches);

        if ($targets) {
            $targetsFilter = $this->filterUtils->composeRegex($targets, '/');
            
            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $targetsFilter,
                PatchDefinition::TARGETS
            );

            $subset = array_merge(
                !$patches ? array_values(preg_grep($targetsFilter, $names)) : array(),
                $this->patchListUtils->getAllTargets($patches)
            );

            $resets = array_intersect($resets, $subset);
        }

        $resets = array_merge(
            $this->patchListUtils->getRelatedTargets($patches, $resets),
            $resets
        );
        
        return array($patches, $resets);
    }
}
