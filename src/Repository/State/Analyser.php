<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\State;

class Analyser
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer
     */
    private $patchListTransformer;

    public function __construct()
    {
        $this->patchListTransformer = new \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer();
    }

    public function collectPatchRemovals(array $repositoryState, array $patches)
    {
        $patchesByTarget = $this->patchListTransformer->groupItemsByTarget($patches);

        $result = array();

        $unpackedState = $this->patchListTransformer->createDetailedList($repositoryState);

        foreach ($unpackedState as $target => $items) {
            $result[$target] = array_diff_key(
                $items,
                isset($patchesByTarget[$target]) ? $patchesByTarget[$target] : array()
            );
        }

        return $result;
    }

    public function collectPatchIncludes(array $repositoryState, array $patches)
    {
        $patchesByTarget = $this->patchListTransformer->groupItemsByTarget($patches);
        $patchesFootprints = $this->patchListTransformer->createSimplifiedList($patchesByTarget);

        $result = array();

        foreach ($patchesFootprints as $target => $items) {
            $result[$target] = array_diff_key(
                $items,
                isset($repositoryState[$target]) ? $repositoryState[$target] : array()
            );
        }

        return $this->patchListTransformer->createDetailedList($result);
    }
}
