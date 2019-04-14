<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class FilteredListResolver implements \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
{
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
     * @param array $filters
     */
    public function __construct(
        array $filters = array()
    ) {
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();

        $this->filters = $filters;
    }

    public function resolvePatchesQueue(array $patches)
    {
        $patches = array_filter($patches);

        foreach ($this->filters as $key => $filter) {
            $patches = $this->patchListUtils->applyDefinitionFilter(
                $patches,
                $this->filterUtils->composeRegex($filter, '/'),
                $key
            );
        }

        if (array_filter($this->filters)) {
            $patches = $this->patchListUtils->embedInfoToItems($patches, array(
                Patch::STATUS => 'match',
                Patch::STATUS_MATCH => true
            ));
        }

        return $patches;
    }

    public function resolveRelevantPatches(array $patches, array $subset)
    {
        return $patches;
    }
    
    public function resolveInitialState(array $patches, array $state)
    {
        $unpackedState = $this->patchListUtils->createDetailedList($state);
        
        $patchesByTarget = $this->patchListUtils->groupItemsByTarget($patches);
        
        foreach ($patchesByTarget as $items) {
            foreach ($items as $path => $item) {
                if (!$item[Patch::STATUS_MATCH]) {
                    continue;
                }

                foreach (array_keys($unpackedState) as $fpTarget) {
                    unset($unpackedState[$fpTarget][$path]);
                }
            }
        }
        
        return $this->patchListUtils->createSimplifiedList($unpackedState);
    }
}
