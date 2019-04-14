<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SequenceComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        if (isset($data[PatchDefinition::BEFORE]) && !is_array($data[PatchDefinition::BEFORE])) {
            $data[PatchDefinition::BEFORE] = array($data[PatchDefinition::BEFORE]);
        }

        if (isset($data[PatchDefinition::AFTER]) && !is_array($data[PatchDefinition::AFTER])) {
            $data[PatchDefinition::AFTER] = array($data[PatchDefinition::AFTER]);
        }

        return array(
            PatchDefinition::BEFORE => isset($data[PatchDefinition::BEFORE])
                ? $data[PatchDefinition::BEFORE]
                : array(),
            PatchDefinition::AFTER => isset($data[PatchDefinition::AFTER])
                ? $data[PatchDefinition::AFTER]
                : array()
        );
    }
}
