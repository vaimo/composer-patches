<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class DefaultValuesComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        return array(
            PatchDefinition::PATH => isset($data[PatchDefinition::PATH]) && file_exists($data[PatchDefinition::PATH])
                ? $data[PatchDefinition::PATH]
                : '',
            PatchDefinition::STATUS_NEW => true,
            PatchDefinition::STATUS_CHANGED => true,
            PatchDefinition::STATUS_MATCH => false,
            PatchDefinition::STATUS_LABEL => '',
            PatchDefinition::CHECKSUM => ''
        );
    }
}
