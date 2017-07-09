<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchListUpdaterInterface
{
    /**
     * @param array $patchList
     * @return array
     */
    public function update(array $patchList);
}
