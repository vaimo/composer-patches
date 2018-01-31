<?php
namespace Vaimo\ComposerPatches\Utils;

class FilterUtils
{
    public function composeRegex(array $filters, $delimiter)
    {
        $negations = array();
        $affirmations = array();
        
        array_map(function ($filter) use ($delimiter, &$negations, &$affirmations) {
            $isNegation = substr($filter, 0, 1) == '!';

            $escapedFilter = trim(
                str_replace(
                    chr('27'),
                    '.*',
                    preg_quote(
                        str_replace('*', chr('27'), ltrim($filter,'!')),
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
    
    public function filterBySubItemKeys($groups, $filter)
    {
        return array_map(function ($group) use ($filter) {
            $keys = array_filter(array_keys($group), function ($path) use ($filter) {
                return preg_match($filter, $path);
            });

            return array_intersect_key(
                $group,
                array_flip($keys)
            );
        }, $groups);
    }
}
