<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Config as PluginConfig;

class Config
{
    const BUNDLE_TARGET = '*';
    
    const ENABLED = 'enable-patching';
    const ENABLED_FOR_PACKAGES = 'enable-patching-from-packages';

    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(
        array $config
    ) {
        $this->config = $config;
    }

    public function isPatchingEnabled()
    {
        if (empty($this->config[PluginConfig::LIST]) && !isset($this->config[self::ENABLED])) {
            return false;
        }

        return !isset($this->config[self::ENABLED]) || $this->config[self::ENABLED];
    }

    public function isPackageScopeEnabled()
    {
        if (!isset($this->config[self::ENABLED_FOR_PACKAGES])) {
            return true;
        }

        return $this->config[self::ENABLED_FOR_PACKAGES];
    }
}
