<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\State;

class Analyser
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    public function __construct() 
    {
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }
    
    public function collectPatchRemovals(array $repositoryState, array $patches)
    {
        $patchesByTarget = $this->patchListUtils->groupItemsByTarget($patches);
        $patchesFootprints = $this->patchListUtils->createSimplifiedList($patchesByTarget);

        $result = array();

        foreach ($repositoryState as $target => $items) {
            $result[$target] = array_diff_key(
                $items,
                isset($patchesFootprints[$target]) ? $patchesFootprints[$target] : array()
            );
        }
        
        return $this->patchListUtils->createDetailedList($result);
    }

    public function collectPatchIncludes(array $repositoryState, array $patches)
    {
        $patchesByTarget = $this->patchListUtils->groupItemsByTarget($patches);
        $patchesFootprints = $this->patchListUtils->createSimplifiedList($patchesByTarget);

        $result = array();

        foreach ($patchesFootprints as $target => $items) {
            $result[$target] = array_diff_key(
                $items,
                isset($repositoryState[$target]) ? $repositoryState[$target] : array()
            );
        }

        return $this->patchListUtils->createDetailedList($result);
    }
}
