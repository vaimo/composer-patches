<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;

class SourcesResolverFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @param \Composer\Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    public function create(PluginConfig $pluginConfig)
    {
        $patcherConfig = $pluginConfig->getPatcherConfig();

        $sourceConfig = $this->resolveSourceConfig($patcherConfig);

        $listSources = array(
            'project' => new \Vaimo\ComposerPatches\Sources\ProjectSource($this->composer->getPackage()),
            'vendors' => new \Vaimo\ComposerPatches\Sources\VendorSource(
                isset($sourceConfig['vendors']) && is_array($sourceConfig['vendors'])
                    ? $sourceConfig['vendors']
                    : array()
            ),
            'packages' => new \Vaimo\ComposerPatches\Sources\PackageSource(
                isset($sourceConfig['packages']) && is_array($sourceConfig['packages'])
                    ? $sourceConfig['packages']
                    : array()
            )
        );

        return new \Vaimo\ComposerPatches\Patch\SourcesResolver(
            array_intersect_key(
                $listSources,
                array_filter($sourceConfig)
            )
        );
    }

    private function resolveSourceConfig($patcherConfig)
    {
        $sourceConfig = $patcherConfig[PluginConfig::PATCHER_SOURCES];

        if (isset($sourceConfig['packages'], $sourceConfig['vendors'])) {
            if (is_array($sourceConfig['packages']) && !is_array($sourceConfig['vendors'])) {
                $sourceConfig['vendors'] = false;
            } elseif (is_array($sourceConfig['vendors']) && !is_array($sourceConfig['packages'])) {
                $sourceConfig['packages'] = false;
            } elseif ($sourceConfig['packages'] === false || $sourceConfig['vendors'] === false) {
                $sourceConfig['packages'] = false;
                $sourceConfig['vendors'] = false;
            }
        }

        return $sourceConfig;
    }
}
