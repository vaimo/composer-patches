<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class Analyser
{
    public function getAllTargets(array $patches)
    {
        $targetList = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchInfo) {
                $targetList = array_merge(
                    $targetList,
                    $patchInfo[Patch::TARGETS]
                );
            }
        }

        return array_values(
            array_unique($targetList)
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
        } while (!empty($scanTargets));

        return $result;
    }

    public function extractValue(array $patches, array $keys)
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
