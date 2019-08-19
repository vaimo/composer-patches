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
            if (!is_array($data[PatchDefinition::VERSION])) {
                $dependsTarget = $target;

                $dependsPatterns = $this->generateDependencyMatchPatterns($ownerConfig);
                
                if ($dependsPatterns) {
                    $matches = array_filter(
                        array_keys($dependsPatterns),
                        function ($pattern) use ($target) {
                            return preg_match('#' . $pattern . '#', $target);
                        }
                    );
                    
                    if (!empty($matches)) {
                        $dependsTarget = $dependsPatterns[reset($matches)];
                    }
                }

                $data[PatchDefinition::VERSION] = array(
                    $dependsTarget => $data[PatchDefinition::VERSION]
                );
            }

            $depends = array_replace(
                $depends,
                $data[PatchDefinition::VERSION]
            );
        }
        
        return array(
            PatchDefinition::DEPENDS => $depends
        );
    }
    
    private function generateDependencyMatchPatterns(array $config)
    {
        $dependsConfig = array();
        
        if (isset($config[PluginConfig::PATCHES_DEPENDS])) {
            $dependsConfig = $config[PluginConfig::PATCHES_DEPENDS];

            if (!is_array($dependsConfig)) {
                $dependsConfig = array(
                    PluginConfig::PATCHES_CONFIG_DEFAULT => $dependsConfig
                );
            }
        }
        
        $patterns = array_map(function ($candidate) {
            return trim($candidate, '*')
                ? str_replace(chr(32), '.*', preg_quote(str_replace('*', chr(32), $candidate), '#'))
                : preg_quote($candidate);
        }, array_keys($dependsConfig));

        $patternValues = array_combine($patterns, $dependsConfig);

        if (isset($patternValues[PluginConfig::PATCHES_CONFIG_DEFAULT])) {
            $patternValues = array_replace(
                $patternValues,
                array('.*' => $patternValues[PluginConfig::PATCHES_CONFIG_DEFAULT])
            );
            
            unset($patternValues[PluginConfig::PATCHES_CONFIG_DEFAULT]);
        }
        
        return $patternValues;
    }
}
