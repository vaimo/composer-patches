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

                    $path = $patchInfo['url'] ? $patchInfo['url'] : $patchPath;
                    
                    $patchesByTarget[$target][$path] = sprintf(
                        '%s, %s:%s', 
                        $patchInfo[PatchDefinition::LABEL], 
                        PatchDefinition::HASH, 
                        $patchInfo[PatchDefinition::HASH]
                    );
                }
            }
        }

        return $patchesByTarget;
    } 
    
    public function sanitizeFileSystem(array $patches)
    {
        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                if (!isset($patchInfo[PatchDefinition::TMP]) || !$patchInfo[PatchDefinition::TMP]) {
                    continue;
                }

                unlink($patchInfo[PatchDefinition::PATH]);
            }
        }
    }
}
