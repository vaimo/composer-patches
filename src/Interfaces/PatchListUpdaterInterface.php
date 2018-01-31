<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchListUpdaterInterface
{
    /**
     * @param array $patchList
     * @return array
     */
    public function update(array $patchList);
}
