<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SkipComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        $source = $data[PatchDefinition::SOURCE];
        $flag = '#' . PatchDefinition::SKIP;

        if (strstr($source, $flag) !== $flag) {
            return array(
                PatchDefinition::SKIP => isset($data[PatchDefinition::SKIP])
                    ? $data[PatchDefinition::SKIP]
                    : false
            );
        }

        return array(
            PatchDefinition::SOURCE => substr($source, 0, strrpos($source, $flag)),
            PatchDefinition::SKIP => true
        );
    }
}
