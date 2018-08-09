<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class BasePathComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\TemplateUtils
     */
    private $templateUtils;

    public function __construct()
    {
        $this->templateUtils = new \Vaimo\ComposerPatches\Utils\TemplateUtils();
    }

    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        if (!isset($ownerConfig[PluginConfig::PATCHES_BASE])) {
            return array();
        }

        $source = $data[PatchDefinition::SOURCE];

        if (parse_url($source, PHP_URL_SCHEME)) {
            return array();
        }

        if (isset($data[PatchDefinition::LABEL]) && is_numeric($label)) {
            $label = $data[PatchDefinition::LABEL];
        }

        if (strpos($data[PatchDefinition::PATH], '/') === 0 && file_exists($data[PatchDefinition::PATH])) {
            return array(
                PatchDefinition::LABEL => $label,
                PatchDefinition::SOURCE => $source
            );
        }

        $template = $this->resolveTemplate($ownerConfig, $target);

        $nameParts = explode('/', $target);

        $sourceTags = '';

        if (strstr($source, '#') !== false) {
            $sourceSegments = explode('#', $source);
            $sourceTags = array_pop($sourceSegments);
            $source = implode('#', $sourceSegments);
        }

        $pathVariables = array(
            'file' => $source,
            'vendor' => array_shift($nameParts),
            'package' => implode('/', $nameParts),
            'label' => $label
        );

        $nameParts = array_map(function ($part) {
            $part = strtolower(
                preg_replace(
                    array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'),
                    array('\\1_\\2', '\\1_\\2'),
                    str_replace('_', '.', $part)
                )
            );

            return preg_replace('/\s{2,}/', ' ', str_replace(array(' ', '_', '-', '.', '/', ':'), ' ', $part));
        }, $pathVariables);

        $mutationNamesMap = array(
            'file' => 'file name',
            'vendor' => 'vendor name',
            'package' => 'module name',
            'label' => 'label value'
        );

        $mutationAppliers = $this->createMutationAppliers();

        $pathVariables = array();
        $mutatedNames = array_fill_keys(array_keys($mutationNamesMap), array());

        foreach ($nameParts as $name => $value) {
            $variableName = $mutationNamesMap[$name];

            foreach ($mutationAppliers as $mutationApplier) {
                $mutationName = $mutationApplier($variableName);
                $pathVariables[$mutationName] = $mutationApplier($value);
                $mutatedNames[$name][] = $mutationName;
            }
        }

        $extraVariables = array(
            'version' => preg_replace(
                '/[^A-Za-z0-9.-]/',
                '',
                strtok(reset($data[PatchDefinition::DEPENDS]) ?: '0.0.0', ' ')
            ),
            'file' => $source,
            'label' => $label
        );

        $variablePattern = '{{%s}}';

        $mutationRules = $this->templateUtils->collectValueMutationRules($template, array($variablePattern));

        $templateVariables = array_replace(
            $this->templateUtils->applyMutations(
                array_replace($pathVariables, $extraVariables),
                $mutationRules,
                ' :-_.'
            ),
            $pathVariables,
            $extraVariables
        );

        $source = $this->templateUtils->compose(
            $template . ($sourceTags ? ('#' . $sourceTags) : ''),
            $templateVariables,
            array_fill_keys(array($variablePattern), false)
        );

        $filename = basename($source);

        if (substr($label, -strlen($filename)) == $filename) {
            $label = str_replace(
                $filename,
                preg_replace('/\s{2,}/', ' ', preg_replace('/[^A-Za-z0-9]/', ' ', strtok($filename, '.'))),
                $label
            );
        }

        return array(
            PatchDefinition::LABEL => $label,
            PatchDefinition::SOURCE => $source
        );
    }

    private function resolveTemplate($ownerConfig, $packageName)
    {
        $templates = $ownerConfig[PluginConfig::PATCHES_BASE];

        list($vendorName, ) = explode('/', $packageName);

        if (is_array($templates)) {
            if (isset($templates[$packageName])) {
                return $templates[$packageName];
            } else if (isset($templates[$vendorName])) {
                return $templates[$vendorName];
            } else if ($templates[PluginConfig::PATCHES_BASE_DEFAULT]) {
                return $templates[PluginConfig::PATCHES_BASE_DEFAULT];
            } else {
                return reset($templates);
            }
        }

        return $templates;
    }

    private function createMutationAppliers()
    {
        return array(
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
    }
}
