<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Bootstrap
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Vaimo\ComposerPatches\Factories\ConfigFactory
     */
    private $configFactory;

    /**
     * @var \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory
     */
    private $loaderFactory;

    /**
     * @var \Vaimo\ComposerPatches\Factories\PatchesApplierFactory
     */
    private $applierFactory;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     * @param array $config
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $io,
        $config = array()
    ) {
        $this->composer = $composer;
        $this->config = $config;

        $this->configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory();
        $this->loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($io);
        $this->applierFactory = new \Vaimo\ComposerPatches\Factories\PatchesApplierFactory($io);

        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }

    public function applyPatches($devMode = false, array $targets = array(), array $filters = array())
    {
        $config = $this->configFactory->create($this->composer, array($this->config));

        $patchesApplier = $this->applierFactory->create($this->composer, $config, $targets, $filters);

        if (!$patchesApplier) {
            return null;
        }

        $patchesLoader = $this->loaderFactory->create($this->composer, $config, $devMode);

        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $patches = $patchesLoader->loadFromPackagesRepository($repository);
        
        $patchesApplier->apply($repository, $patches);
    }

    public function stripPatches($devMode = false, array $targets = array(), array $filters = array())
    {
        $sources = array($this->config);

        if ($filters) {
            $filters = $this->filterUtils->invert($filters);
        } else {
            $sources[] = array(\Vaimo\ComposerPatches\Config::PATCHER_SOURCES => false);
        }

        $config = $this->configFactory->create($this->composer, $sources);

        $patchesApplier = $this->applierFactory->create($this->composer, $config, $targets, $filters);
        $patchesLoader = $this->loaderFactory->create($this->composer, $config, $devMode);

        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $patches = $patchesLoader->loadFromPackagesRepository($repository);

        $patchesApplier->apply($repository, $patches);
    }
}
