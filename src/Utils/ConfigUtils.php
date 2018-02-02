<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Vaimo\ComposerPatches\Config;

class ConfigUtils
{
    public function mergeApplierConfig(array $config, array $updates)
    {
        $config[Config::PATCHER_APPLIERS] = array_replace_recursive(
            $config[Config::PATCHER_APPLIERS],
            isset($updates[Config::PATCHER_APPLIERS]) ? $updates[Config::PATCHER_APPLIERS] : array()
        );

        $config[Config::PATCHER_SEQUENCE] = array_replace(
            $config[Config::PATCHER_SEQUENCE],
            isset($updates[Config::PATCHER_SEQUENCE]) ? $updates[Config::PATCHER_SEQUENCE] : array()
        );

        if (isset($updates[Config::PATCHER_SOURCES])) {
            if ($updates[Config::PATCHER_SOURCES]) {
                $config[Config::PATCHER_SOURCES] = array_replace(
                    $config[Config::PATCHER_SOURCES],
                    $updates[Config::PATCHER_SOURCES]
                );
            } else {
                $config[Config::PATCHER_SOURCES] = array();
            }
        }
        
        $config[Config::PATCHER_OPERATIONS] = array_replace(
            $config[Config::PATCHER_OPERATIONS],
            isset($updates[Config::PATCHER_OPERATIONS]) ? $updates[Config::PATCHER_OPERATIONS] : array()
        );

        $config[Config::PATCHER_LEVELS] = isset($updates[Config::PATCHER_LEVELS])
            ? $updates[Config::PATCHER_LEVELS]
            : $config[Config::PATCHER_LEVELS];
        
        return $config;
    }
    
    public function sortApplierConfig(array $config)
    {
        $sequences = $config[Config::PATCHER_SEQUENCE];
        $sequencedConfigItems = array_keys($sequences);
        
        foreach ($sequencedConfigItems as $item) {
            if (!isset($config[$item])) {
                continue;
            }
            
            $config[$item] = array_replace(
                array_flip($sequences[$item]),
                array_intersect_key(
                    $config[$item],
                    array_flip($sequences[$item])
                )
            );
        }
        
        return $config;
    }
}
