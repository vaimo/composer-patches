<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class PathComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        $source = isset($data[PatchDefinition::URL])
            ? $data[PatchDefinition::URL]
            : $data[PatchDefinition::SOURCE];
        
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

            
            
            $pathVariables['version'] = preg_replace(
                '/[^A-Za-z0-9.-]/', 
                '', 
                strtok(reset($data[PatchDefinition::DEPENDS]), ' ')
            );

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

        return array(
            PatchDefinition::SOURCE => ($basePath ? $basePath . DIRECTORY_SEPARATOR : '') . $source,
        );
    }
}
