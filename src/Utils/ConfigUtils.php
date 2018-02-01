<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class ConfigUtils
{
    public function mergeApplierConfig(array $config, array $updates)
    {
        $config['patchers'] = array_replace_recursive(
            $config['patchers'],
            isset($updates['patchers']) ? $updates['patchers'] : array()
        );

        $config['sequence'] = array_replace(
            $config['sequence'],
            isset($updates['sequence']) ? $updates['sequence'] : array()
        );

        $config['operations'] = array_replace(
            $config['operations'],
            isset($updates['operations']) ? $updates['operations'] : array()
        );

        $config['levels'] = isset($updates['levels'])
            ? $updates['levels']
            : $config['levels'];
        
        return $config;
    }
    
    public function sortApplierConfig(array $config)
    {
        $config['patchers'] = array_replace(
            array_flip($config['sequence']['patchers']),
            array_intersect_key($config['patchers'], array_flip($config['sequence']['patchers']))
        );
        
        $config['operations'] = array_replace(
            array_flip($config['sequence']['operations']),
            array_intersect_key($config['operations'], array_flip($config['sequence']['operations']))
        );
        
        return $config;
    }
}