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
        $config[Config::PATCHER_PROVIDERS] = array_replace_recursive(
            $config[Config::PATCHER_PROVIDERS],
            isset($updates[Config::PATCHER_PROVIDERS]) ? $updates[Config::PATCHER_PROVIDERS] : array()
        );

        $config[Config::PATCHER_SEQUENCE] = array_replace(
            $config[Config::PATCHER_SEQUENCE],
            isset($updates[Config::PATCHER_SEQUENCE]) ? $updates[Config::PATCHER_SEQUENCE] : array()
        );

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
