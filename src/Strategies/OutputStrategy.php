<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Strategies;

class OutputStrategy
{
    public function shouldAllowForPatches(array $patches, array $outputTriggers)
    {
        $muteTriggersMatcher = array_flip($outputTriggers);

        return (bool)array_filter($patches, function (array $patch) use ($muteTriggersMatcher) {
            return array_filter(
                array_intersect_key($patch, $muteTriggersMatcher)
            );
        });
    }
}
