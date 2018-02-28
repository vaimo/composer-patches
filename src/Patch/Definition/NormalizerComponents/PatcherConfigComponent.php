<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class PatcherConfigComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        $defaults = array_fill_keys(
            array(PluginConfig::PATCHER_SEQUENCE, PluginConfig::PATCHER_LEVELS),
            array()
        );

        $config = array_replace(
            $defaults,
            isset($data[PatchDefinition::CONFIG]) ? $data[PatchDefinition::CONFIG] : array()
        );

        if (isset($data[PatchDefinition::LEVEL])) {
            $config = array_replace(
                $config,
                array(PluginConfig::PATCHER_LEVELS => array($data[PatchDefinition::LEVEL]))
            );
        }

        /**
         * Applier sequence configuration
         */
        if (isset($data[PatchDefinition::PATCHER])) {
            $config[PluginConfig::PATCHER_SEQUENCE][PluginConfig::PATCHER_APPLIERS] = array(
                $data[PatchDefinition::PATCHER]
            );
        }

        return array(
            PatchDefinition::CONFIG => $config
        );
    }
}
