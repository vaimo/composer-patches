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
                PatchDefinition::ISSUE => isset($data[PatchDefinition::ISSUE])
                    ? $data[PatchDefinition::ISSUE]
                    : false,
                PatchDefinition::LINK => isset($data[PatchDefinition::LINK])
                    ? $data[PatchDefinition::LINK]
                    : false,
                PatchDefinition::LABEL => isset($data[PatchDefinition::LABEL])
                    ? $data[PatchDefinition::LABEL]
                    : $label,
                PatchDefinition::TARGETS => isset($data[PatchDefinition::TARGETS]) && $target === PatchDefinition::BUNDLE_TARGET
                    ? $data[PatchDefinition::TARGETS]
                    : array($target),
                PatchDefinition::CWD => isset($data[PatchDefinition::CWD])
                    ? $data[PatchDefinition::CWD]
                    : PatchDefinition::CWD_INSTALL,
                PatchDefinition::LEVEL => isset($data[PatchDefinition::LEVEL])
                    ? $data[PatchDefinition::LEVEL]
                    : null,
                PatchDefinition::LEVEL => isset($data[PatchDefinition::LEVEL])
                    ? $data[PatchDefinition::LEVEL]
                    : null,
                PatchDefinition::CATEGORY => isset($data[PatchDefinition::CATEGORY])
                    ? $data[PatchDefinition::CATEGORY]
                    : null,
            );
    }
}
