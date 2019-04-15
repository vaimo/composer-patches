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
        $semanticGroups = array_fill_keys(array(0, 1), array());

        $escapeChar = chr('27');

        array_map(function ($filter) use ($delimiter, &$semanticGroups, $escapeChar) {
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

            $semanticGroups[(int)$this->isInvertedFilter($filter)][] = $escapedFilter;
        }, $filters);

        $pattern = '%s';

        if ($semanticGroups[0]) {
            $pattern = sprintf(
                '^((?!.*(%s)).*%s)',
                implode('|', $semanticGroups[0]),
                $semanticGroups[1] ? '(%s)' : ''
            );
        }

        return $delimiter . sprintf($pattern, implode('|', $semanticGroups[1])) . $delimiter;
    }

    public function invertRules(array $filters)
    {
        return array_map(function ($filter) {
            return (!$this->isInvertedFilter($filter) ? FilterUtils::NEGATION_PREFIX : '') .
                ltrim($filter, FilterUtils::NEGATION_PREFIX);
        }, $filters);
    }
    
    private function isInvertedFilter($filter)
    {
        return strpos($filter, FilterUtils::NEGATION_PREFIX) === 0;
    }

    public function trimRules(array $filters)
    {
        return array_map(function ($filter) {
            return ltrim($filter, FilterUtils::NEGATION_PREFIX);
        }, $filters);
    }
}
