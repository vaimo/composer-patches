<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

class DirectListResolver implements \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
{
    public function resolvePatchesQueue(array $patches)
    {
        return array_filter($patches);
    }
    
    public function resolveRelevantPatches(array $patches, array $subset)
    {
        return $patches;
    }
}
