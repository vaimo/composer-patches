<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Config
{
    const PACKAGE_CONFIG_FILE = 'composer.json';
    const CONFIG_ROOT = 'extra';
    
    const LIST = 'patches';
    const DEV_LIST = 'patches-dev';
    const FILE = 'patches-file';
    const DEV_FILE = 'patches-file-dev';
    
    const EXCLUDED_PATCHES = 'excluded-patches';
    
    const APPLIED_FLAG = 'patches_applied';
    const PATCHER_PLUGIN_MARKER = 'patcher_plugin';

    const PATCHER_CONFIG_ROOT = 'patcher';
    const PATCHER_APPLIERS = 'appliers';
    const PATCHER_OPERATIONS = 'operations';
    const PATCHER_SEQUENCE = 'sequence';
    const PATCHER_LEVELS = 'levels';
    const PATCHER_SOURCES = 'sources';
    const PATCHER_SECURE_HTTP = 'secure-http';
    
    const PATCHER_ARG_LEVEL = 'level';
    const PATCHER_ARG_FILE = 'file';
    const PATCHER_ARG_CWD = 'cwd';

    /**
     * @var array
     */
    private $config;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\ConfigUtils
     */
    private $configUtils;

    /**
     * @param array $config
     */
    public function __construct(
        array $config
    ) {
        $this->config = $config;
        
        $this->configUtils = new \Vaimo\ComposerPatches\Utils\ConfigUtils();
    }
    
    public function shouldPreferOwnerPackageConfig()
    {
        return (bool)getenv(Environment::PREFER_OWNER);
    }
    
    public function shouldResetEverything()
    {
        return (bool)getenv(Environment::FORCE_REAPPLY) || (bool)getenv('COMPOSER_FORCE_PATCH_REAPPLY');
    }
    
    public function shouldExitOnFirstFailure()
    {
        return (bool)getenv(Environment::EXIT_ON_FAIL) || (bool)getenv('COMPOSER_EXIT_ON_PATCH_FAILURE');
    }
    
    public function getSkippedPackages()
    {
        $skipList = getenv(Environment::PACKAGE_SKIP)
            ? getenv(Environment::PACKAGE_SKIP)
            : getenv('COMPOSER_SKIP_PATCH_PACKAGES');
            
        return array_filter(
            explode(',', $skipList)
        );
    }
    
    public function getPatcherConfig(array $overrides = array())
    {
        $config = $this->configUtils->mergeApplierConfig($this->config, $overrides);
        
        return $this->configUtils->sortApplierConfig($config);
    }
}
