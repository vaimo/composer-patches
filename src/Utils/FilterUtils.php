<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class FilterUtils
{
    const AFFIRMATION = 0;
    const NEGATION = 1;
    
    const NEGATION_PREFIX = '!';

    public function composeRegex(array $filters, $delimiter)
    {
        $semanticGroups = array_fill_keys(array(0, 1), array());

        $escapeChar = chr('27');

        array_map(function ($filter) use ($delimiter, &$semanticGroups, $escapeChar) {
            $isNegation = strpos($filter, FilterUtils::NEGATION_PREFIX) === 0;

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

            $semanticGroups[(int)$isNegation][] = $escapedFilter;
        }, $filters);

        $pattern = '%s';

        if ($semanticGroups[self::NEGATION]) {
            $pattern = sprintf(
                '^((?!.*(%s)).*%s)',
                implode('|', $semanticGroups[self::NEGATION]),
                $semanticGroups[self::AFFIRMATION] ? '(%s)' : ''
            );
        }

        return $delimiter . 
            sprintf($pattern, implode('|', $semanticGroups[self::AFFIRMATION])) . 
            $delimiter;
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
