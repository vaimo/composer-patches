<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Package\ConfigExtractors;

class PatcherConfigReaderFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    public function create(PluginConfig $pluginConfig)
    {
        $composerConfig = $this->composer->getConfig();

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $this->composer->getInstallationManager(),
            $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR)
        );

        $infoExtractor = $pluginConfig->shouldPreferOwnerPackageConfig()
            ? new ConfigExtractors\VendorConfigExtractor($packageInfoResolver)
            : new ConfigExtractors\InstalledConfigExtractor();

        return new \Vaimo\ComposerPatches\Patcher\ConfigReader($infoExtractor);
    }
}
