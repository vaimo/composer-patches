<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class Updater
{
    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param array $patches
     * @param array|bool|string $update
     * @param bool $onlyNew
     * @return array
     */
    public function embedInfoToItems(array $patches, $update, $onlyNew = false)
    {
        foreach ($patches as $target => $group) {
            foreach (array_keys($group) as $path) {
                $patches[$target][$path] = is_array($update)
                    ? array_replace(
                        $patches[$target][$path],
                        $onlyNew ? array_diff_key($update, array_filter($patches[$target][$path])) : $update
                    )
                    : $update;
            }
        }

        return $patches;
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
}
