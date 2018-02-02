<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;

class ConfigFactory
{
    public function __construct() 
    {
        $this->defaults = new \Vaimo\ComposerPatches\Config\Defaults();
        $this->configUtils = new \Vaimo\ComposerPatches\Utils\ConfigUtils();
    }

    public function create(\Composer\Composer $composer, array $configSources)
    {
        $defaults = $this->defaults->getPatcherConfig();
        $extra = $composer->getPackage()->getExtra();

        if (isset($extra['patcher-config']) && !isset($extra[PluginConfig::PATCHER_CONFIG_ROOT])) {
            $extra[PluginConfig::PATCHER_CONFIG_ROOT] = $extra['patcher-config'];
        }
        
        $patcherConfig = isset($extra[PluginConfig::PATCHER_CONFIG_ROOT])
            ? $extra[PluginConfig::PATCHER_CONFIG_ROOT]
            : array();
        
        if ($patcherConfig === false) {
            $patcherConfig = array(
                PluginConfig::PATCHER_SOURCES => false
            );
        }

        if (isset($patcherConfig['patchers']) && !isset($patcherConfig[PluginConfig::PATCHER_APPLIERS])) {
            $patcherConfig[PluginConfig::PATCHER_APPLIERS] = $patcherConfig['patchers'];
            unset($patcherConfig['patchers']);
        }
        
        if (!isset($patcherConfig[PluginConfig::PATCHER_SOURCES])) {
            if (isset($extra['enable-patching']) && !$extra['enable-patching']) {
                $patcherConfig[PluginConfig::PATCHER_SOURCES] = false;
            } else if (isset($extra['enable-patching-from-packages']) && !$extra['enable-patching-from-packages']) {
                $patcherConfig[PluginConfig::PATCHER_SOURCES] = array('packages' => false, 'vendors' => false);
            }
        }
        
        if ($patcherConfig) {
            array_unshift($configSources, $patcherConfig);
        }
        
        $config = array_reduce(
            $configSources,
            array($this->configUtils, 'mergeApplierConfig'),
            $defaults
        );
        
        return new PluginConfig($config);
    }
}
