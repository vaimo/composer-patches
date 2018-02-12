<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface DefinitionNormalizerComponentInterface
{
    /**
     * @param string $target
     * @param string $label
     * @param array $data
     * @param array $ownerConfig
     * @return array
     */
    public function normalize($target, $label, array $data, array $ownerConfig);
}
