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
     * @var array
     */
    private $filters;
    
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer
     */
    private $patchListTransformer;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Updater
     */
    private $patchListUpdater;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @param array $filters
     */
    public function __construct(
        array $filters = array()
    ) {
        $this->filters = $filters;

        $this->patchListTransformer = new \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer();
        $this->patchListUpdater = new \Vaimo\ComposerPatches\Patch\DefinitionList\Updater();
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function resolvePatchesQueue(array $patches)
    {
        $patches = array_filter($patches);

        foreach ($this->filters as $key => $filter) {
            $patches = $this->patchListUtils->applyDefinitionKeyValueFilter(
                $patches,
                $this->filterUtils->composeRegex($filter, '/'),
                $key
            );
        }

        if (array_filter($this->filters)) {
            $patches = $this->patchListUpdater->embedInfoToItems($patches, array(
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
        $unpackedState = $this->patchListTransformer->createDetailedList($state);
        
        $patchesByTarget = $this->patchListTransformer->groupItemsByTarget($patches);
        
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
        
        return $this->patchListTransformer->createSimplifiedList($unpackedState);
    }
}
