<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class Transformer
{
    public function createSimplifiedList(array $patches)
    {
        $groups = $this->createTargetsList($patches);

        return array_map(function ($group) {
            $fingerprints = array_map(function ($item) {
                return sprintf(
                    '%s, %s:%s',
                    isset($item[Patch::LABEL]) ? $item[Patch::LABEL] : '{no label}',
                    Patch::HASH,
                    isset($item[Patch::HASH]) && $item[Patch::HASH] ? $item[Patch::HASH] : '{no hash}'
                );
            }, $group);

            $keys = array_map(
                function ($key, $item) {
                    return sprintf(
                        '%s%s%s',
                        $item[Patch::OWNER],
                        Patch::SOURCE_INFO_SEPARATOR,
                        $key
                    );
                },
                array_keys($group),
                $group
            );

            return array_combine($keys, $fingerprints);
        }, $groups);
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

                $matches = array();
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

        foreach ($patchesList as $group) {
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
}
