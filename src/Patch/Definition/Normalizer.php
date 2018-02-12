<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition;

use Vaimo\ComposerPatches\Patch\Definition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class Normalizer
{
    public function process($target, $label, $data, array $ownerConfig)
    {
        if (!is_array($data)) {
            $data = array(
                Definition::SOURCE => (string)$data
            );
        }

        if (!isset($data[Definition::URL]) && !isset($data[Definition::SOURCE])) {
            return false;
        }
        
        $source = isset($data[Definition::URL])
            ? $data[Definition::URL]
            : $data[Definition::SOURCE];

        $sourceSegments = explode('#', $source);
        $lastSegment = array_pop($sourceSegments);
        
        if ($lastSegment === Definition::SKIP) {
            $source = implode('#', $sourceSegments);
            $data[Definition::SKIP] = true;
        }
        
        $config = array_replace(
            array(PluginConfig::PATCHER_SEQUENCE => array(), PluginConfig::PATCHER_LEVELS => array()),
            isset($config[Definition::CONFIG])
                ? $config[Definition::CONFIG]
                : array()
        );
        
        /**
         * Patch constraints
         */
        $depends = isset($data[Definition::DEPENDS]) 
            ? $data[Definition::DEPENDS] 
            : array();
        
        if (isset($data[Definition::VERSION])) {
            if (is_array($data[Definition::VERSION])) {
                $depends = array_replace(
                    $depends, 
                    $data[Definition::VERSION]
                );
            } else {
                $dependsTarget = isset($ownerConfig[PluginConfig::PATCHES_DEPENDS]) 
                    ? $ownerConfig[PluginConfig::PATCHES_DEPENDS]
                    : $target;
                
                $depends = array_replace(
                    $depends, 
                    array($dependsTarget => $data[Definition::VERSION])
                );
            }
        }

        /**
         * Patch path
         */
        $sourcePathInfo = parse_url($source);
        $sourceIncludesUrlScheme = isset($sourcePathInfo['scheme']) && $sourcePathInfo['scheme'];

        $basePath = '';
        
        if (isset($ownerConfig[PluginConfig::PATCHES_BASE]) && !$sourceIncludesUrlScheme) {
            $nameParts = explode('/', $target);

            $pathVariables = array(
                'vendor' => array_shift($nameParts),
                'package' => implode('/', $nameParts)
            );

            $nameParts = array_map(function ($part) {
                $part = strtolower(
                    preg_replace(
                        array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'),
                        array('\\1_\\2', '\\1_\\2'),
                        str_replace('_', '.', $part)
                    )
                );

                return str_replace(array(' ', '_', '-', '.', '/'), ' ', $part);
            }, $pathVariables);

            $mutationNames = array(
                'vendor' => 'vendor name',
                'package' => 'module name'
            );

            $mutationAppliers = array(
                function ($value) {
                    return str_replace(' ', '', $value);
                },
                function ($value) {
                    return str_replace(' ', '', ucwords($value));
                },
                function ($value) {
                    return str_replace(' ', '', ucfirst($value));
                },
                function ($value) {
                    return str_replace(' ', '-', $value);
                },
                function ($value) {
                    return str_replace(' ', '_', $value);
                },
            );

            $pathVariables = array();

            foreach ($nameParts as $name => $value) {
                $variableName = $mutationNames[$name];
                foreach ($mutationAppliers as $mutationApplier) {
                    $mutationName = $mutationApplier($variableName);
                    $pathVariables[$mutationName] = $mutationApplier($value);
                }
            }

            $pathVariables['version'] = preg_replace('/[^A-Za-z0-9.-]/', '', strtok(reset($depends), ' '));

            $pathVariables = array_combine(
                array_map(function ($name) {
                    return sprintf('{{%s}}', $name);
                }, array_keys($pathVariables)),
                $pathVariables
            );
            
            $basePath = str_replace(
                array_keys($pathVariables), 
                $pathVariables,
                $ownerConfig[PluginConfig::PATCHES_BASE]
            );
        }
        
        /**
         * Patch sequencing
         */
        if (isset($data[Definition::BEFORE]) && !is_array($data[Definition::BEFORE])) {
            $data[Definition::BEFORE] = array($data[Definition::BEFORE]);
        }

        if (isset($data[Definition::AFTER]) && !is_array($data[Definition::AFTER])) {
            $data[Definition::AFTER] = array($data[Definition::AFTER]);
        }

        /**
         * Patched file path strip level
         */
        if (isset($data[Definition::LEVEL])) {
            $config = array_replace(
                $config,
                array(PluginConfig::PATCHER_LEVELS => array($data[Definition::LEVEL]))
            );
        }

        /**
         * Applier sequence configuration
         */
        if (isset($data[Definition::PATCHER])) {
            $config[PluginConfig::PATCHER_SEQUENCE][PluginConfig::PATCHER_APPLIERS] = array(
                $data[Definition::PATCHER]
            );
        }
        
        return array(
            Definition::PATH => '',
            Definition::CHANGED => true,
            Definition::BEFORE => isset($data[Definition::BEFORE]) ? $data[Definition::BEFORE] : array(),
            Definition::AFTER => isset($data[Definition::AFTER]) ? $data[Definition::AFTER] : array(), 
            Definition::URL => $sourceIncludesUrlScheme ? $source : false,
            Definition::SOURCE => ($basePath ? $basePath . DIRECTORY_SEPARATOR : '') . $source,
            Definition::TARGETS => isset($data[Definition::TARGETS]) && $target === Definition::BUNDLE_TARGET
                ? $data[Definition::TARGETS]
                : array($target),
            Definition::SKIP => isset($data[Definition::SKIP])
                ? $data[Definition::SKIP]
                : false,
            Definition::LABEL => isset($data[Definition::LABEL])
                ? $data[Definition::LABEL]
                : $label,
            Definition::DEPENDS => $depends,
            Definition::CONFIG => $config
        );
    }
}
