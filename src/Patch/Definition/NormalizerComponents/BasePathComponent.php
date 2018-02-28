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

        $nameParts = explode('/', $target);

        $pathVariables = array(
            'file' => $source,
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

        $mutationNamesMap = array(
            'file' => 'file name',
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
            'file' => $source
        );

        $variablePattern = '{{%s}}';
        $template = $ownerConfig[PluginConfig::PATCHES_BASE];

        $mutationRules = $this->templateUtils->collectValueMutationRules($template, array($variablePattern));

        $templateVariables = array_replace(
            $this->templateUtils->applyMutations($pathVariables, $mutationRules),
            $pathVariables,
            $extraVariables
        );

        $namesWithRules = array_keys(array_reduce($mutationRules, 'array_replace', array()));

        if (
            strstr($template, sprintf($variablePattern, 'file')) === false
            && !array_intersect($mutatedNames['file'], $namesWithRules)
        ) {
            $template .= DIRECTORY_SEPARATOR . sprintf($variablePattern, 'file');
        }

        return array(
            PatchDefinition::SOURCE => $this->templateUtils->compose(
                $template,
                $templateVariables,
                array_fill_keys(array($variablePattern), false)
            ),
        );
    }
}
