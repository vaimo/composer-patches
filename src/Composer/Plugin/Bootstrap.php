<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Plugin;

class Bootstrap
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Vaimo\ComposerPatches\Composer\Context
     */
    private $composerContext;

    /**
     * @param \Composer\Composer $composer
     * @param \Vaimo\ComposerPatches\Composer\Context $composerContext
     */
    public function __construct(
        \Composer\Composer $composer,
        \Vaimo\ComposerPatches\Composer\Context $composerContext
    ) {
        $this->composer = $composer;
        $this->composerContext = $composerContext;
    }

    public function preloadPluginClasses()
    {
        $installationManager = $this->composer->getInstallationManager();
        $composerConfig = $this->composer->getConfig();

        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($this->composer->getPackage())
        );

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $installationManager,
            $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR)
        );

        $sourcesPreloader = new \Vaimo\ComposerPatches\Package\SourcesPreloader($packageInfoResolver);

        $packages = $this->composerContext->getActivePackages();

        $pluginPackage = $packageResolver->resolveForNamespace($packages, __CLASS__);

        $sourcesPreloader->preload($pluginPackage);
    }
}
