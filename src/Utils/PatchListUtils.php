<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PatchListUtils
{
    public function createSimplifiedList(array $patches, array $targets = array())
    {
        $patchesByTarget = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                foreach ($patchInfo[PatchDefinition::TARGETS] as $target) {
                    if (!isset($patchesByTarget[$target])) {
                        $patchesByTarget[$target] = array();
                    }

                    $patchesByTarget[$target][$patchPath] = $patchInfo[PatchDefinition::LABEL];
                }
            }
        }

        if (!$targets) {
            return $patchesByTarget;
        }

        return array_intersect_key(
            $patchesByTarget,
            array_flip($targets)
        );
    }    
}
