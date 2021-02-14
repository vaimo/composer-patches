<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class DataUtils
{
    public function listToGroups(array $items, $keyMatcher)
    {
        $result = array();

        $key = '';

        foreach ($items as $item) {
            $matches = array();
            if (preg_match($keyMatcher, $item, $matches)) {
                $key = isset($matches['match'])
                    ? $matches['match']
                    : $matches[1];

                continue;
            }

            if (!isset($result[$key])) {
                $result[$key] = array();
            }

            $result[$key][] = $item;
        }

        return $result;
    }
    
    public function embedGroupKeyToItems(array $groups, $template = '%s:%s')
    {
        $result = array();
        
        foreach ($groups as $key => $items) {
            $result[$key] = $this->prefixArrayValues($items, $key, $template);
        }

        return $result;
    }
    
    public function extractOrderedItems(array $items, array $targets)
    {
        $targets = array_flip($targets);

        return array_replace(
            array_intersect_key($targets, $items),
            array_intersect_key($items, $targets)
        );
    }
    
    public function prefixArrayValues(array $data, $prefix, $template = '%s%s')
    {
        return array_map(
            function ($value) use ($prefix, $template) {
                return sprintf($template, $prefix, $value);
            },
            $data
        );
    }
    
    public function extractItems(array &$data, array $keys)
    {
        $subset = array_intersect($data, $keys);
        $data = array_diff($data, $keys);

        return $subset;
    }

    public function extractValue(array $data, $key, $default = null)
    {
        return isset($data[$key])
            ? $data[$key]
            : $default;
    }
    
    public function removeKeysByPrefix(array $data, $prefix)
    {
        $whitelist = array_filter(
            array_keys($data),
            function ($key) use ($prefix) {
                return strpos($key, $prefix) !== 0;
            }
        );
        
        return array_intersect_key(
            $data,
            array_flip($whitelist)
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
                } elseif ($segment === '*') {
                    $stack[] = array(&$node, $item[1]);
                } elseif (isset($node[$segment])) {
                    $stack[] = array(array(&$node[$segment]), $item[1]);
                }
                
                unset($node);
            }
        }
        
        return $result;
    }

    public function getValueByPath(array $data, $path, $default = null)
    {
        if (!is_array($path)) {
            $path = explode('/', $path);
        }

        foreach ($path as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
                
                continue;
            }

            return $default;
        }

        return $data;
    }
}
