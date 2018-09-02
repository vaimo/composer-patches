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

    const PREFIX = 'patches-';

    const DEFINITIONS_LIST = 'patches';
    const DEFINITIONS_FILE = 'patches-file';
    const DEFINITIONS_SEARCH = 'patches-search';

    const DEV_DEFINITIONS_LIST = 'patches-dev';
    const DEV_DEFINITIONS_FILE = 'patches-file-dev';
    const DEV_DEFINITIONS_SEARCH = 'patches-search-dev';

    const EXCLUDED_PATCHES = 'patches-exclude';

    const APPLIED_FLAG = 'patches_applied';
    const PATCHER_PLUGIN_MARKER = 'patcher_plugin';

    const PATCHER_CONFIG_ROOT = 'patcher';
    const PATCHER_APPLIERS = 'appliers';
    const PATCHER_OPERATIONS = 'operations';
    const PATCHER_SEQUENCE = 'sequence';
    const PATCHER_LEVELS = 'levels';
    const PATCHER_SOURCES = 'sources';
    const PATCHER_SECURE_HTTP = 'secure-http';
    const PATCHER_GRACEFUL = 'graceful';

    const PATCHER_FORCE_RESET = 'force-reset';

    const PATCHES_DEPENDS = 'patches-depend';
    const PATCHES_BASE = 'patches-base';

    const PATCHES_BASE_DEFAULT = 'default';

    const PATCHES_CONFIG_DEFAULT = 'default';

    const PATCHER_ARG_LEVEL = 'level';
    const PATCHER_ARG_FILE = 'file';
    const PATCHER_ARG_CWD = 'cwd';

    const PATCH_FILE_REGEX_MATCHER = '/^.+\.patch/i';

    const PATCHER_FROM_SOURCE = 'from-source';
    const PATCHER_FORCE_REAPPLY = 'force-reapply';

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

    public function shouldExitOnFirstFailure()
    {
        return !$this->config[self::PATCHER_GRACEFUL];
    }

    public function shouldPreferOwnerPackageConfig()
    {
        return (bool)$this->config[self::PATCHER_FROM_SOURCE];
    }

    public function shouldForcePackageReset()
    {
        return $this->config[self::PATCHER_FORCE_RESET] || (bool)getenv(Environment::FORCE_RESET);
    }

    public function shouldResetEverything()
    {
        return (bool)$this->config[self::PATCHER_FORCE_REAPPLY];
    }

    public function getPatcherConfig(array $overrides = array())
    {
        $config = $this->configUtils->mergeApplierConfig($this->config, $overrides);

        return $this->configUtils->sortApplierConfig($config);
    }
}
