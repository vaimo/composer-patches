<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class DataUtils
{
    public function extractOrderedItems(array $items, array $targets)
    {
        $targets = array_flip($targets);

        return array_replace(
            array_intersect_key($targets, $items),
            array_intersect_key($items, $targets)
        );
    }
    
    public function prefixArrayValues(array $data, $prefix)
    {
        return array_map(
            function ($value) use ($prefix) {
                return $prefix . $value;
            }, $data
        );
    }
    
    public function removeKeysByPrefix(array $data, $prefix)
    {
        return array_intersect_key(
            $data,
            array_flip(
                array_filter(
                    array_keys($data),
                    function ($key) use ($prefix) {
                        return strpos($key, $prefix) !== 0;
                    }
                )
            )
        );
    }

    public function walkArrayNodes(array $list, \Closure $callback)
    {
        $list = $callback($list);

        foreach ($list as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $list[$key] = $this->walkArrayNodes($value, $callback);
        }

        return $list;
    }
    
    public function getNodeReferencesByPaths(array &$data, array $paths)
    {
        $stack = array();

        foreach ($paths as $path) {
            $stack[] = array(array(&$data), explode('/', $path));
        }

        $result = array();
        
        while ($item = array_shift($stack)) {
            $segment = array_shift($item[1]);

            foreach ($item[0] as &$node) {
                if ($segment === null) {
                    $result[] = &$node;
                } else if ($segment === '*') {
                    $stack[] = array(&$node, $item[1]);
                } else if (isset($node[$segment])) {
                    $stack[] = array(array(&$node[$segment]), $item[1]);
                }
                
                unset($node);
            }
        }
        
        return $result;
    }
}
