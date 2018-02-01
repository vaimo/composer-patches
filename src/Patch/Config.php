<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch;

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
     * @var array
     */
    private $sourceKeys;

    /**
     * @param array $config
     * @param array $sourceKeys
     */
    public function __construct(
        array $config,
        array $sourceKeys = array()
    ) {
        $this->config = $config;
        $this->sourceKeys = $sourceKeys;
    }

    public function isPatchingEnabled()
    {
        if (!isset($this->config[self::ENABLED])) {
            return array_intersect_key(
                $this->config,
                array_flip($this->sourceKeys)
            );
        }
        
        return $this->config[self::ENABLED];
    }

    public function isPackageScopeEnabled()
    {
        if (!isset($this->config[self::ENABLED_FOR_PACKAGES])) {
            return true;
        }

        return $this->config[self::ENABLED_FOR_PACKAGES];
    }
}
