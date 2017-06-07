<?php
namespace Vaimo\ComposerPatches\Patch;

class Config
{
    const ENABLED = 'enable-patching';

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
        if (empty($this->config['patches']) && !isset($this->config[self::ENABLED])) {
            return false;
        }

        return !isset($this->config[self::ENABLED]) || $this->config[self::ENABLED];
    }

    public function isPackageScopeEnabled()
    {
        if (!isset($this->config['enable-patching-from-packages'])) {
            return true;
        }

        return $this->config['enable-patching-from-packages'];
    }
}
