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
     * @var \Vaimo\ComposerPatches\Factories\PatchesRepositoryFactory
     */
    private $repositoryFactory;

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
        $this->repositoryFactory = new \Vaimo\ComposerPatches\Factories\PatchesRepositoryFactory($io);
        $this->applierFactory = new \Vaimo\ComposerPatches\Factories\PatchesApplierFactory($io);

        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }

    public function applyPatches($devMode = false, array $targets = array(), array $filters = array())
    {
        $config = $this->configFactory->create($this->composer, array($this->config));

        $patchesApplier = $this->applierFactory->create($this->composer, $config);

        if (!$patchesApplier) {
            return null;
        }

        $repository = $this->repositoryFactory->create($this->composer, $config, $devMode);

        $patchesApplier->apply($repository, $targets, $filters);
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

        $patchesApplier = $this->applierFactory->create($this->composer, $config);
        $repository = $this->repositoryFactory->create($this->composer, $config, $devMode);

        $patchesApplier->apply($repository, $targets, $filters);
    }
}
