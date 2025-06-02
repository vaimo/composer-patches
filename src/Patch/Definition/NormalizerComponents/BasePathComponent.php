<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Composer\Constants as ComposerConstants;

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
        if ($this->shouldSkip($ownerConfig, $data)) {
            return array();
        }

        $source = $data[PatchDefinition::SOURCE];

        if (is_numeric($label) && isset($data[PatchDefinition::LABEL])) {
            $label = $data[PatchDefinition::LABEL];
        }

        if (strpos($data[PatchDefinition::PATH], DIRECTORY_SEPARATOR) === 0
            && file_exists($data[PatchDefinition::PATH])
        ) {
            return array(
                PatchDefinition::LABEL => $label,
                PatchDefinition::SOURCE => $source
            );
        }

        $template = $this->resolveTemplate($ownerConfig, $target);
        list ($sourcePath, $sourceTags) = $this->deconstructSource($source);
        $nameParts = explode(ComposerConstants::PACKAGE_SEPARATOR, $target);

        $pathVariables = array(
            'file' => $sourcePath,
            'vendor' => array_shift($nameParts),
            'package' => implode(ComposerConstants::PACKAGE_SEPARATOR, $nameParts),
            'label' => $label
        );

        $mutationNamesMap = array(
            'file' => 'file name',
            'vendor' => 'vendor name',
            'package' => 'module name',
            'label' => 'label value'
        );

        $extraVariables = array(
            'version' => preg_replace(
                '/[^A-Za-z0-9.-]/',
                '',
                strtok(reset($data[PatchDefinition::DEPENDS]) ?: '0.0.0', ' ')
            ),
            'file' => $sourcePath,
            'label' => $label
        );

        $variablePattern = '{{%s}}';

        $pathVariables = $this->expandPathVariables($pathVariables, $mutationNamesMap);

        $templateVariables = $this->prepareTemplateValues(
            $template,
            $variablePattern,
            array_replace($pathVariables, $extraVariables)
        );

        $sourcePath = $this->templateUtils->compose(
            $template . ($sourceTags ? ('#' . $sourceTags) : ''),
            $templateVariables,
            array_fill_keys(array($variablePattern), array())
        );

        return array(
            PatchDefinition::LABEL => $this->normalizeLabelForSourcePath($label, $sourcePath),
            PatchDefinition::SOURCE => $sourcePath
        );
    }

    private function shouldSkip(array $ownerConfig, array $data)
    {
        if (!isset($ownerConfig[PluginConfig::PATCHES_BASE])) {
            return true;
        }

        if (parse_url($data[PatchDefinition::SOURCE], PHP_URL_SCHEME)) {
            return true;
        }

        return false;
    }

    private function normalizeLabelForSourcePath($label, $sourcePath)
    {
        $filename = basename($sourcePath);

        if (substr($label, -strlen($filename)) === $filename) {
            return str_replace(
                $filename,
                preg_replace('/\s{2,}/', ' ', preg_replace('/[^A-Za-z0-9]/', ' ', strtok($filename, '.'))),
                $label
            );
        }

        return $label;
    }

    private function deconstructSource($source)
    {
        $sourceTags = '';

        if (strpos($source, '#') !== false) {
            $sourceSegments = explode('#', $source);

            $sourceTags = array_pop($sourceSegments);
            $source = implode('#', $sourceSegments);
        }

        return array($source, $sourceTags);
    }

    private function prepareTemplateValues($template, $variablePattern, $variableValues)
    {
        $mutationRules = $this->templateUtils->collectValueMutationRules(
            $template,
            array($variablePattern)
        );

        return array_replace(
            $this->templateUtils->applyMutations(
                $variableValues,
                $mutationRules,
                ' :-_.'
            ),
            $variableValues
        );
    }

    private function expandPathVariables(array $pathVariables, array $mutationNamesMap)
    {
        $normalizedVariables = array_map(function ($part) {
            $part = strtolower(
                preg_replace(
                    array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'),
                    array('\\1_\\2', '\\1_\\2'),
                    str_replace('_', '.', $part)
                )
            );

            return preg_replace('/\s{2,}/', ' ', str_replace(array(' ', '_', '-', '.', '/', ':'), ' ', $part));
        }, $pathVariables);

        $mutationAppliers = $this->createMutationAppliers();

        $pathVariables = array();

        foreach ($normalizedVariables as $name => $value) {
            $variableName = $mutationNamesMap[$name];

            foreach ($mutationAppliers as $mutationApplier) {
                $mutationName = $mutationApplier($variableName);
                $pathVariables[$mutationName] = $mutationApplier($value);
            }
        }

        return $pathVariables;
    }

    private function resolveTemplate($ownerConfig, $packageName)
    {
        $templates = $ownerConfig[PluginConfig::PATCHES_BASE];

        $vendorName = strtok($packageName, ComposerConstants::PACKAGE_SEPARATOR);

        if (is_array($templates)) {
            if (isset($templates[$packageName])) {
                return $templates[$packageName];
            } elseif (isset($templates[$vendorName])) {
                return $templates[$vendorName];
            } elseif ($templates[PluginConfig::PATCHES_BASE_DEFAULT]) {
                return $templates[PluginConfig::PATCHES_BASE_DEFAULT];
            }

            return reset($templates);
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
