<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

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
            $patches = $this->patchListUtils->embedInfoToItems(
                $patches,
                array(PatchDefinition::STATUS_LABEL => 'MATCH')
            );
        }

        return $patches;
    }
}
