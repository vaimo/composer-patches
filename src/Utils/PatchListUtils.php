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
            return array_map(function($item) {
                return sprintf(
                    '%s, %s:%s',
                    isset($item[Patch::LABEL]) ? $item[Patch::LABEL] : '{no label}',
                    Patch::HASH, isset($item[Patch::HASH]) ? $item[Patch::HASH] : '{no hash}'
                );
            }, $group);
        }, $groups);

        return $result;
    }

    public function createDetailedList(array $patches)
    {
        $result = array();
        
        foreach ($patches as $owner => $group) {
            $result[$owner] = array();
            
            if (!is_array($group)) {
                continue;
            }
            
            foreach ($group as $path => $label) {
                $result[$owner][$path] = array(
                    'path' => $path,
                    'targets' => array($owner),
                    'source' => $path,
                    'owner' => Patch::OWNER_UNKNOWN,
                    'label' => implode(',', array_slice(explode(',', $label), 0, -1))
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
        $result = $targets;

        $relatedPatches = array();

        do {
            $scanQueue = array_diff_key($patchesList, array_flip($result));

            $resets = array();

            foreach ($scanQueue as $owner => $patches) {
                foreach ($patches as $patchPath => $patchInfo) {
                    if (!array_intersect($patchInfo[Patch::TARGETS], $result)) {
                        continue;
                    }
                    
                    if (!isset($relatedPatches[$owner])) {
                        $relatedPatches[$owner] = array();
                    }

                    $relatedPatches[$owner][$patchPath] = $patchInfo;

                    $resets = array_merge(
                        $resets, 
                        array_diff($patchInfo[Patch::TARGETS], $result)
                    );
                }
            }

            $result = array_merge($result, array_unique($resets));
        } while ($resets);

        return $relatedPatches;
    }

    public function generateKnownPatchFlagUpdates($ownerName, array $resetPatchesList, array $infoList)
    {
        $updates = array();

        if (!isset($resetPatchesList[$ownerName])) {
            $resetPatchesList[$ownerName] = array_reduce($resetPatchesList, 'array_replace', array());
        }

        $resetInfoList = array_replace(
            $infoList,
            array(
                $ownerName => array_reduce(
                    array_intersect_key($infoList, $resetPatchesList),
                    'array_replace',
                    array()
                )
            )
        );

        foreach ($resetPatchesList as $targetName => $resetPatches) {
            if (!isset($resetInfoList[$targetName])) {
                continue;
            }

            $knownPatches = array_intersect_assoc($resetInfoList[$targetName], $resetPatches);

            $changedPatches = array_diff_key(
                array_intersect_key($resetInfoList[$targetName], $resetPatches),
                $knownPatches
            );

            $patchDefinitionUpdates = array_replace(
                array_fill_keys(
                    array_keys($changedPatches),
                    array(Patch::STATUS_NEW => false)
                ),
                array_fill_keys(
                    array_keys($knownPatches),
                    array(Patch::STATUS_NEW => false, Patch::STATUS_CHANGED => false)
                )
            );

            $updates = array_replace_recursive(
                $updates,
                array($targetName => $patchDefinitionUpdates)
            );
        }

        return $updates;
    }

    public function updateList(array $patches, array $updates)
    {
        foreach ($patches as $target => $group) {
            foreach ($group as $path => $item) {
                $patches[$target][$path] = array_replace(
                    $patches[$target][$path], 
                    isset($updates[$target][$path]) ? $updates[$target][$path] : array() 
                );
            }
        }

        $patches = array_map(
            function ($items) {
                return array_filter($items, function ($item) {
                    return array_filter($item);
                });
            },
            $patches
        );

        return array_filter(
            array_map('array_filter', $patches)
        );
    }

    public function embedInfoToItems(array $patches, array $updates)
    {
        foreach ($patches as $target => $group) {
            foreach ($group as $path => $item) {
                $patches[$target][$path] = array_replace(
                    $patches[$target][$path], 
                    $updates
                );
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
}
