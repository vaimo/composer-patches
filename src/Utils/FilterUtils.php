<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class FilterUtils
{
    const NEGATION_PREFIX = '!';

    public function composeRegex(array $filters, $delimiter)
    {
        $negations = array();
        $affirmations = array();

        $escapeChar = chr('27');

        array_map(function ($filter) use ($delimiter, &$negations, &$affirmations, $escapeChar) {
            $isNegation = substr($filter, 0, 1) == FilterUtils::NEGATION_PREFIX;

            $escapedFilter = trim(
                str_replace(
                    $escapeChar,
                    '.*',
                    preg_quote(
                        str_replace('*', $escapeChar, ltrim($filter, FilterUtils::NEGATION_PREFIX)),
                        $delimiter
                    )
                )
            );

            if (!$escapedFilter) {
                return;
            }

            if ($isNegation) {
                $negations[] = $escapedFilter;
            } else {
                $affirmations[] = $escapedFilter;
            }
        }, $filters);

        $pattern = '%s';

        if ($negations) {
            $pattern = sprintf('^((?!.*(%s)).*%s)', implode('|', $negations), $affirmations ? '(%s)' : '');
        }

        return $delimiter . sprintf($pattern, implode('|', $affirmations)) . $delimiter;
    }

    public function invertRules(array $filters)
    {
        return array_map(function ($filter) {
            $isNegation = substr($filter, 0, 1) == FilterUtils::NEGATION_PREFIX;

            return (!$isNegation ? FilterUtils::NEGATION_PREFIX : '') .
                ltrim($filter, FilterUtils::NEGATION_PREFIX);
        }, $filters);
    }

    public function trimRules(array $filters)
    {
        return array_map(function ($filter) {
            return ltrim($filter, FilterUtils::NEGATION_PREFIX);
        }, $filters);
    }
}
