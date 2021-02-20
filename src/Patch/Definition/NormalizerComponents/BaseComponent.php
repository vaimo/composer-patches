<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class BaseComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        return array(
            Patch::ISSUE => $this->extractValue($data, Patch::ISSUE, false),
            Patch::LINK => $this->extractValue($data, Patch::LINK, false),
            Patch::LABEL => $this->extractValue($data, Patch::LABEL, $label),
            Patch::CWD => $this->extractValue($data, Patch::CWD, Patch::CWD_INSTALL),
            Patch::LEVEL => $this->extractValue($data, Patch::LEVEL),
            Patch::CATEGORY => $this->extractValue($data, Patch::CATEGORY),
            Patch::LOCAL => $this->extractValue($data, Patch::LOCAL, false),
            Patch::TARGETS => isset($data[Patch::TARGETS]) && $target === Patch::BUNDLE_TARGET
                ? $data[Patch::TARGETS]
                : array($target)
        );
    }

    private function extractValue(array $data, $key, $default = null)
    {
        return isset($data[$key])
            ? $data[$key]
            : $default;
    }
}
