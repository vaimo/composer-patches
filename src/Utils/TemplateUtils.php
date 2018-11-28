<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class TemplateUtils
{
    public function collectValueMutationRules($template, array $patterns)
    {
        $result = array();

        foreach ($patterns as $pattern) {
            preg_match_all('/' . sprintf($pattern, '([^\}]+)'). '/', $template, $usedVariables);

            foreach ($usedVariables[1] as $variableName) {
                if (!preg_match_all('/\(([^\)]+)\)/', $variableName, $valueRules)) {
                    continue;
                }

                $valueExtractor = str_replace(
                    str_replace($valueRules[0], '', $variableName),
                    '(.*)',
                    str_replace('(', '(?:', $variableName)
                );

                $normalizedName = str_replace($valueRules[0], '', $variableName);

                $result[$variableName] = array($normalizedName => $valueExtractor);
            }
        }

        return $result;
    }

    public function applyMutations(array $arguments, array $mutations, $trimRules = ' ')
    {
        $result = array();

        foreach ($mutations as $mutationName => $mutation) {
            if (!$matches = array_intersect_key($mutation, $arguments)) {
                continue;
            }

            $argumentName = key($matches);

            if (preg_match_all('/' . reset($matches) . '/i', $arguments[$argumentName], $valueMatches)) {
                $result[$mutationName] = trim(reset($valueMatches[1]), $trimRules);
            } else {
                $result[$mutationName] = trim($arguments[$argumentName], $trimRules);
            }
        }

        return $result;
    }

    public function compose($template, array $arguments, array $patterns)
    {
        $variables = array();

        foreach ($patterns as $format => $escaper) {
            $variables = array_replace(
                $variables,
                array_combine(
                    array_map(function ($item) use ($format) {
                        return sprintf($format, $item);
                    }, array_keys($arguments)),
                    $escaper ? array_map($escaper, $arguments) : $arguments
                )
            );
        }
        
        return str_replace(
            array_keys($variables), 
            array_map('trim', $variables), 
            $template
        );
    }
}
