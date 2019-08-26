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
            preg_match_all('/' . sprintf($pattern, '([^\}]+)') . '/', $template, $usedVariables);

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
            $matches = array_intersect_key($mutation, $arguments);
            
            if (empty($matches)) {
                continue;
            }

            $argumentName = key($matches);

            $value = $arguments[$argumentName];
            
            if (preg_match_all(sprintf('/%s/i', reset($matches)), $arguments[$argumentName], $valueMatches)) {
                $value = reset($valueMatches[1]);
            }

            $result[$mutationName] = trim($value, $trimRules);
        }

        return $result;
    }

    public function compose($template, array $arguments, array $variableFormats)
    {
        $updateGroups = array();
        
        foreach ($variableFormats as $format => $escapers) {
            $templateArguments = $arguments;
            
            if ($escapers) {
                foreach ($escapers as $escaper) {
                    $templateArguments = array_map($escaper, $arguments);
                }
            }
            
            $updateGroups[] = array_combine(
                array_map(
                    function ($item) use ($format) {
                        return sprintf($format, $item);
                    },
                    array_keys($arguments)
                ),
                $templateArguments
            );
        }

        $variables = array_reduce($updateGroups, 'array_replace', array());
        
        $names = array_keys($variables);
        
        $values = array_map(
            function ($value) {
                return trim(
                    strtok($value, PHP_EOL)
                );
            },
            $variables
        );
        
        return str_replace($names, $values, $template);
    }
}
