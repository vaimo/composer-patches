<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\ResetsResolvers;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

class RelatedResetsResolver implements \Vaimo\ComposerPatches\Interfaces\PatchesResetsResolverInterface
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
     * @param \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser
     */
    public function __construct(
        \Vaimo\ComposerPatches\Repository\Analyser $repositoryAnalyser
    ) {
        $this->repositoryAnalyser = $repositoryAnalyser;

        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
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
}
