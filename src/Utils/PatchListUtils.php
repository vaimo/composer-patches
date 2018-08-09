<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PatchListUtils
{
    public function createSimplifiedList(array $patches)
    {
        $patchesByTarget = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                foreach ($patchInfo[PatchDefinition::TARGETS] as $target) {
                    if (!isset($patchesByTarget[$target])) {
                        $patchesByTarget[$target] = array();
                    }

                    $path = (isset($patchInfo['url']) && $patchInfo['url']) ? $patchInfo['url'] : $patchPath;

                    $patchesByTarget[$target][$path] = sprintf(
                        '%s, %s:%s',
                        isset($patchInfo[PatchDefinition::LABEL])
                            ? $patchInfo[PatchDefinition::LABEL]
                            : '{no label}',
                        PatchDefinition::HASH,
                        isset($patchInfo[PatchDefinition::HASH])
                            ? $patchInfo[PatchDefinition::HASH]
                            : '{no hash}'
                    );
                }
            }
        }

        return $patchesByTarget;
    }

    public function sanitizeFileSystem(array $patches)
    {
        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                if (!isset($patchInfo[PatchDefinition::TMP]) || !$patchInfo[PatchDefinition::TMP]) {
                    continue;
                }

                unlink($patchInfo[PatchDefinition::PATH]);
            }
        }
    }

    public function getAllTargets(array $patches)
    {
        $targetList = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchInfo) {
                $targetList = array_merge($targetList, $patchInfo[PatchDefinition::TARGETS]);
            }
        }

        return array_unique($targetList);
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

                $patchInfo = false;
            }
        }

        return array_filter(array_map('array_filter', $patches));
    }

    public function getRelatedTargets(array $patches, array $targets)
    {
        $result = $targets;

        do {
            $resetQueueUpdates = array();

            foreach (array_diff_key($patches, array_flip($result)) as $packagePatches) {
                foreach ($packagePatches as $patchInfo) {
                    if (array_intersect($patchInfo[PatchDefinition::TARGETS], $result)) {
                        $resetQueueUpdates = array_merge(
                            $resetQueueUpdates,
                            array_diff($patchInfo[PatchDefinition::TARGETS], $result)
                        );

                        continue;
                    }
                }
            }

            $result = array_merge($result, array_unique($resetQueueUpdates));
        } while ($resetQueueUpdates);

        return array_diff($result, $targets);
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

            if ($targetName == 'yotpo/module-review') {
                $i = 0;
            }

            $knownPatches = array_intersect_assoc($resetInfoList[$targetName], $resetPatches);
            $changedPatches = array_diff_key(
                array_intersect_key($resetInfoList[$targetName], $resetPatches),
                $knownPatches
            );

            $patchDefinitionUpdates = array_replace(
                array_fill_keys(
                    array_keys($changedPatches),
                    array(PatchDefinition::NEW => false)
                ),
                array_fill_keys(
                    array_keys($knownPatches),
                    array(PatchDefinition::NEW => false, PatchDefinition::CHANGED => false)
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
        $items = array_map(
            function ($items) {
                return array_filter($items, function ($item) {
                    return array_filter($item);
                });
            },
            array_replace_recursive($patches, $updates)
        );

        return array_filter(array_map('array_filter', $items));
    }
}
