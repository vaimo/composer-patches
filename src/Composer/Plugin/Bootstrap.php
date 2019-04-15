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
     * @param \Composer\Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }
    
    public function preloadPluginClasses()
    {
        $installationManager = $this->composer->getInstallationManager();
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $composerConfig = $this->composer->getConfig();

        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($this->composer->getPackage())
        );

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $installationManager,
            $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR)
        );

        $sourcesPreloader = new \Vaimo\ComposerPatches\Package\SourcesPreloader($packageInfoResolver);

        $sourcesPreloader->preload(
            $packageResolver->resolveForNamespace($repository, __NAMESPACE__)
        );
    }
}
