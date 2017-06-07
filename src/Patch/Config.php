<?php
namespace Vaimo\ComposerPatches\Patch;

class Config
{
    /**
     * @var array
     */
    protected $config;

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
        if (empty($this->config['patches'])) {
            return isset($this->config['enable-patching']) ? $this->config['enable-patching'] : false;
        } else {
            return isset($this->config['enable-patching']) && !$this->config['enable-patching'] ? false : true;
        }
    }
}
