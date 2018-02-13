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
     * @var \Vaimo\ComposerPatches\Repository\Processor
     */
    private $repositoryProcessor;

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
        
        $logger = new \Vaimo\ComposerPatches\Logger($io);
        
        $this->configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory();
        $this->loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($io);
        $this->applierFactory = new \Vaimo\ComposerPatches\Factories\PatchesApplierFactory($logger);
        $this->repositoryProcessor = new \Vaimo\ComposerPatches\Repository\Processor($logger);

        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }

    public function applyPatches($devMode = false, array $filters = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $config = $this->configFactory->create($this->composer, array($this->config));

        $patchesLoader = $this->loaderFactory->create($this->composer, $config, $devMode);
        $patchesApplier = $this->applierFactory->create($this->composer, $config, $filters);

        $this->repositoryProcessor->process($repository, $patchesLoader, $patchesApplier);
    }

    public function stripPatches($devMode = false, array $filters = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $sources = array($this->config);

        if ($filters) {
            $filters = array_map(array($this->filterUtils, 'invertRules'), $filters);
        } else {
            $sources[] = array(\Vaimo\ComposerPatches\Config::PATCHER_SOURCES => false);
        }

        $config = $this->configFactory->create($this->composer, $sources);

        $patchesLoader = $this->loaderFactory->create($this->composer, $config, $devMode);
        $patchesApplier = $this->applierFactory->create($this->composer, $config, $filters);

        $this->repositoryProcessor->process($repository, $patchesLoader, $patchesApplier);
    }
}
