<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class BaseComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        return
            array(
                PatchDefinition::LABEL => isset($data[PatchDefinition::LABEL])
                    ? $data[PatchDefinition::LABEL]
                    : $label,
                PatchDefinition::TARGETS => isset($data[PatchDefinition::TARGETS]) && $target === PatchDefinition::BUNDLE_TARGET
                    ? $data[PatchDefinition::TARGETS]
                    : array($target)
            );
    }
}
