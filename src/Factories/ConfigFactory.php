<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Tivie\OS;

class ConfigFactory
{
    /**
     * @var \Vaimo\ComposerPatches\Config\Defaults
     */
    private $defaults;

    /**
     * @var \Vaimo\ComposerPatches\Utils\ConfigUtils
     */
    private $configUtils;

    /**
     * @var OS\Detector
     */
    protected $osDetector;
    
    public function __construct() 
    {
        $this->defaults = new \Vaimo\ComposerPatches\Config\Defaults();
        $this->configUtils = new \Vaimo\ComposerPatches\Utils\ConfigUtils();
        $this->osDetector = new OS\Detector();
    }

    public function create(\Composer\Composer $composer, array $configSources)
    {
        $defaults = $this->defaults->getPatcherConfig();
        $extra = $composer->getPackage()->getExtra();
        
        if (isset($extra['patcher-config']) && !isset($extra[PluginConfig::PATCHER_CONFIG_ROOT])) {
            $extra[PluginConfig::PATCHER_CONFIG_ROOT] = $extra['patcher-config'];
        }
        
        $subConfigKeys = array(
            $this->getOperationSystemName(),
            $this->getOperationSystemFamily(),
            '',
        );
        
        foreach (array_unique($subConfigKeys) as $key) {
            $configRootKey = PluginConfig::PATCHER_CONFIG_ROOT . $key ? ('-' . $key) : '';

            $patcherConfig = isset($extra[$configRootKey]) ? $extra[$configRootKey] : array();

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
        }
        
        $config = array_reduce(
            $configSources,
            array($this->configUtils, 'mergeApplierConfig'),
            $defaults
        );
        
        return new PluginConfig($config);
    }

    public function getOperationSystemName()
    {
        $typeId = $this->osDetector->getType();
        
        $labels = array(
            OS\MACOSX => 'mac',
            OS\GEN_UNIX => 'unix',
            OS\BSD => 'bsd',
            OS\LINUX => 'linux',
            OS\WINDOWS => 'windows',
            OS\SUN_OS => 'sun'
        );

        if (isset($labels[$typeId])) {
            return $labels[$typeId];
        }
        
        return '';
    }
    
    public function getOperationSystemFamily()
    {
        $familyId = $this->osDetector->getFamily();
        
        $labels = array(
            OS\UNIX_FAMILY => 'unix',
            OS\WINDOWS_FAMILY => 'windows'
        );

        if (isset($labels[$familyId])) {
            return $labels[$familyId];
        }

        return '';
    }
}
