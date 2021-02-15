<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class PatchListUtils
{
    public function compareLists(array $listA, array $listB, \Closure $logicProvider)
    {
        $matches = array();
        
        foreach ($listB as $name => $itemsB) {
            $itemsA = isset($listA[$name]) ? $listA[$name] : array();

            if (!$logicProvider($itemsA, $itemsB)) {
                continue;
            }

            $matches[] = $name;
        }

        return $matches;
    }
    
    public function sanitizeFileSystem(array $patches)
    {
        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchInfo) {
                if (!isset($patchInfo[Patch::TMP]) || !$patchInfo[Patch::TMP]) {
                    continue;
                }

                if (file_exists($patchInfo[Patch::PATH])) {
                    unlink($patchInfo[Patch::PATH]);
                }

                $dirName = dirname($patchInfo[Patch::PATH]);
                
                if (!is_dir($dirName)) {
                    continue;
                }

                $iterator = new \FilesystemIterator($dirName);
                
                if (!$iterator->valid()) {
                    rmdir($dirName);
                }
            }
        }
    }
    
    public function applyDefinitionFilter(array $patches, \Closure $logicProvider)
    {
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                $result = $logicProvider($patchData);
                
                if ($result) {
                    continue;
                }

                $patchData = false;
            }
            
            unset($patchData);

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
    
    public function applyDefinitionKeyValueFilter(array $patches, $filter, $key)
    {
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchInfo) {
                if (!isset($patchInfo[$key])) {
                    $patchInfo = false;
                    
                    continue;
                }
                
                if ($this->shouldIncludePatch($patchInfo[$key], $filter)) {
                    continue;
                }

                $patchInfo = false;
            }
        }

        return array_filter(
            array_map('array_filter', $patches)
        );
    }
    
    private function shouldIncludePatch($value, $filter)
    {
        if (is_array($value) && preg_grep($filter, $value)) {
            return true;
        }

        if (is_string($value) && preg_match($filter, $value)) {
            return true;
        }

        if (is_bool($filter) && $value === $filter) {
            return true;
        }
        
        return false;
    }
    
    public function filterListByTargets(array $patches, array $targets)
    {
        foreach ($patches as $target => $group) {
            foreach ($group as $path => $patch) {
                if (array_intersect($patch[Patch::TARGETS], $targets)) {
                    continue;
                }

                unset($patches[$target][$path]);
            }
        }
        
        return array_filter($patches);
    }
    
    public function mergeLists(array $listA, array $listB)
    {
        $result = array();

        $keys = array_unique(
            array_merge(array_keys($listA), array_keys($listB))
        );
        
        foreach ($keys as $key) {
            $result[$key] = array_replace(
                isset($listA[$key]) ? $listA[$key] : array(),
                isset($listB[$key]) ? $listB[$key] : array()
            );
        }

        return $result;
    }
    
    public function diffListsByPath(array $listA, array $listB)
    {
        $pathFlags = array_fill_keys($this->getAllPaths($listB), true);

        return array_map(function (array $group) use ($pathFlags) {
            return array_filter(
                $group,
                function (array $item) use ($pathFlags) {
                    $path = $item[Patch::PATH] ? $item[Patch::PATH] : $item[Patch::URL];
                    
                    return !isset($pathFlags[$path]);
                }
            );
        }, $listA);
    }
    
    public function intersectListsByPath(array $listA, array $listB)
    {
        $pathFlags = array_fill_keys($this->getAllPaths($listB), true);

        return array_map(function (array $group) use ($pathFlags) {
            return array_filter(
                $group,
                function (array $item) use ($pathFlags) {
                    $path = $item[Patch::PATH] ? $item[Patch::PATH] : $item[Patch::URL];

                    return isset($pathFlags[$path]);
                }
            );
        }, $listA);
    }

    public function diffListsByName(array $listA, array $listB)
    {
        foreach ($listB as $target => $group) {
            if (!isset($listA[$target])) {
                $listA[$target] = array();
            }

            $listA[$target] = array_diff_key($listA[$target], $group);
        }

        return $listA;
    }

    public function intersectListsByName(array $listA, array $listB)
    {
        $result = array();
        
        foreach ($listB as $target => $group) {
            if (!isset($listA[$target])) {
                continue;
            }
            
            $result[$target] = array_intersect_key($listA[$target], $group);
        }

        return $result;
    }

    private function getAllPaths($patches)
    {
        return array_reduce(
            $patches,
            function ($result, array $group) {
                return array_merge(
                    $result,
                    array_values(
                        array_map(function (array $item) {
                            return $item[Patch::PATH] ? $item[Patch::PATH] : $item[Patch::URL];
                        }, $group)
                    )
                );
            },
            array()
        );
    }
}
