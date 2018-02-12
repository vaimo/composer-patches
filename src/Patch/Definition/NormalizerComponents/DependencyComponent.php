<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class DependencyComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        $depends = isset($data[PatchDefinition::DEPENDS])
            ? $data[PatchDefinition::DEPENDS]
            : array();

        if (isset($data[PatchDefinition::VERSION])) {
            if (is_array($data[PatchDefinition::VERSION])) {
                $depends = array_replace(
                    $depends,
                    $data[PatchDefinition::VERSION]
                );
            } else {
                $dependsTarget = isset($ownerConfig[PluginConfig::PATCHES_DEPENDS])
                    ? $ownerConfig[PluginConfig::PATCHES_DEPENDS]
                    : $target;

                $depends = array_replace(
                    $depends,
                    array($dependsTarget => $data[PatchDefinition::VERSION])
                );
            }
        }
        
        return array(
            PatchDefinition::DEPENDS => $depends
        );
    }
}
