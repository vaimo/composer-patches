<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class PatchListUtils
{
    public function createSimplifiedList(array $patches)
    {
        $groups = $this->createTargetsList($patches);

        $result = array_map(function ($group) {
            $fingerprints = array_map(function($item) {
                return sprintf(
                    '%s, %s:%s',
                    isset($item[Patch::LABEL]) ? $item[Patch::LABEL] : '{no label}',
                    Patch::HASH, 
                    isset($item[Patch::HASH]) && $item[Patch::HASH] ? $item[Patch::HASH] : '{no hash}'
                );
            }, $group);
            
            $keys = array_map(function ($key, $item) {
                return sprintf('%s%s%s', $item[Patch::OWNER], Patch::SOURCE_INFO_SEPARATOR, $key);
            }, array_keys($group), $group);
            
            return array_combine($keys, $fingerprints);
            
        }, $groups);

        return $result;
    }

    public function createDetailedList(array $patches)
    {
        $result = array();

        $labelInfoMatcher = sprintf('/%s:(?P<hash>.*)/', Patch::HASH);
        
        foreach ($patches as $target => $group) {
            $result[$target] = array();
            
            if (!is_array($group)) {
                continue;
            }
            
            foreach ($group as $sourceInfo => $label) {
                $sourceConfig = explode(Patch::SOURCE_INFO_SEPARATOR, $sourceInfo);

                $path = array_pop($sourceConfig);
                $owner = array_pop($sourceConfig);

                $labelConfig = explode(',', $label);

                preg_match($labelInfoMatcher, trim(end($labelConfig)), $matches);
                
                $result[$target][$path] = array(
                    'path' => $path,
                    'targets' => array($target),
                    'source' => $path,
                    'owner' => $owner ? $owner : Patch::OWNER_UNKNOWN,
                    'label' => implode(',', array_slice($labelConfig, 0, -1)),
                    'md5' => is_array($matches) && isset($matches['hash']) ? $matches['hash'] : null
                );
            }
        }
        
        return $result;
    }
    
    public function createTargetsList(array $patches)
    {
        $result = array();

        foreach ($patches as $originName => $patchGroup) {
            if (!is_array($patchGroup)) {
                continue;
            }

            foreach ($patchGroup as $patchPath => $patchInfo) {
                foreach ($patchInfo[Patch::TARGETS] as $target) {
                    if (!isset($result[$target])) {
                        $result[$target] = array();
                    }

                    $path = (isset($patchInfo['url']) && $patchInfo['url']) ? $patchInfo['url'] : $patchPath;

                    $result[$target][$path] = array_replace(
                        $patchInfo,
                        array(Patch::ORIGIN => $originName)
                    );
                }
            }
        }

        return $result;
    }

    public function groupItemsByTarget(array $patchesList)
    {
        $result = array();

        foreach ($patchesList as $origin => $group) {
            if (!isset($result[$origin])) {
                $result[$origin] = array();
            }

            foreach ($group as $path => $patch) {
                foreach ($patch[Patch::TARGETS] as $target) {
                    $result[$target][$path] = array_replace(
                        $patch,
                        array(Patch::ORIGIN => $origin)
                    );
                }
            }
        }

        return array_filter($result);
    }

    public function createOriginList(array $patchesList)
    {
        $result = array();

        foreach ($patchesList as $target => $group) {
            foreach ($group as $path => $patch) {
                $origin = $patch[Patch::ORIGIN];
                
                if (!isset($result[$origin])) {
                    $result[$origin] = array();
                }
                
                if (isset($result[$origin][$path])) {
                    continue;
                }
                
                $result[$origin][$path] = array_diff_key($patch, array(Patch::ORIGIN => true));
            }
        }

        return array_filter($result);
    }
    
    public function sanitizeFileSystem(array $patches)
    {
        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                if (!isset($patchInfo[Patch::TMP]) || !$patchInfo[Patch::TMP]) {
                    continue;
                }

                unlink($patchInfo[Patch::PATH]);
            }
        }
    }

    public function createFlatList(array $patches)
    {
        $result = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchInfo) {
                $result[] = $patchInfo;
            }
        }

        return $result;
    }
    
    public function getAllTargets(array $patches)
    {
        $targetList = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchInfo) {
                $targetList = array_merge($targetList, $patchInfo[Patch::TARGETS]);
            }
        }

        return array_values(array_unique($targetList));
    }

    public function applyDefinitionFilter(array $patches, $filter, $key)
    {
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchInfo) {
                if (!isset($patchInfo[$key])) {
                    $patchInfo = false;
                    
                    continue;
                }

                $value = $patchInfo[$key];

                if (is_array($value) && preg_grep($filter, $value)) {
                    continue;
                }

                if (is_string($value) && preg_match($filter, $value)) {
                    continue;
                }
                
                if (is_bool($filter) && $value === $filter) {
                    continue;
                }

                $patchInfo = false;
            }
        }

        return array_filter(
            array_map('array_filter', $patches)
        );
    }

    public function getRelatedPatches(array $patchesList, array $targets)
    {
        $scanTargets = $targets;

        $targetsStack = array();

        $result = array();

        do {
            $targetsUpdates = array();

            foreach ($patchesList as $owner => $patches) {
                foreach ($patches as $patchPath => $patchInfo) {
                    if (!array_intersect($patchInfo[Patch::TARGETS], $scanTargets)) {
                        continue;
                    }

                    if (!isset($result[$owner])) {
                        $result[$owner] = array();
                    }

                    $result[$owner][$patchPath] = $patchInfo;

                    $targetsUpdates = array_merge($targetsUpdates, $patchInfo[Patch::TARGETS]);
                }
            }

            $targetsStack = array_unique(
                array_merge($targetsStack, $scanTargets)
            );

            $scanTargets = array_diff($targetsUpdates, $targetsStack, $scanTargets);
        } while ($scanTargets);

        return $result;
    }
    
    public function embedInfoToItems(array $patches, $update)
    {
        foreach ($patches as $target => $group) {
            foreach ($group as $path => $item) {
                $patches[$target][$path] = is_array($update) 
                    ? array_replace($patches[$target][$path], $update) 
                    : $update;
            }
        }

        return $patches;
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
    
    public function getAllPaths($patches)
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
    
    public function updateStatuses(array $patches, $status)
    {
        return array_map(function (array $group) use ($status) {
            return array_map(function (array $patch) use ($status) {
                return array_replace($patch, array(
                    Patch::STATUS => $status
                ));
            }, $group);
        }, $patches);
    }
    
    public function extractValue($patches, array $keys)
    {
        return array_reduce(
            $patches,
            function (array $result, array $items) use ($keys) {
                $values = array_values(
                    array_map(function ($item) use ($keys) {
                        foreach ($keys as $key) {
                            if (!isset($item[$key])) {
                                continue;
                            }
                            
                            if (!$item[$key]) {
                                continue;
                            }
                            
                            return $item[$key];
                        }
                        
                        return null;
                    }, $items)
                );

                return array_merge($result, $values);
            },
            array()
        );
    }    
}
