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
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool
     */
    private $loaderComponents;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     * @parma \Vaimo\ComposerPatches\Factories\ConfigFactory $configFactory
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $io,
        \Vaimo\ComposerPatches\Factories\ConfigFactory $configFactory
    ) {
        $this->composer = $composer;
        $this->configFactory = $configFactory;

        $logger = new \Vaimo\ComposerPatches\Logger($io);

        $this->loaderComponents = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composer,
            $io
        );

        $this->loaderFactory = new \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory($composer);

        $this->applierFactory = new \Vaimo\ComposerPatches\Factories\PatchesApplierFactory(
            $composer,
            $logger
        );

        $this->repositoryProcessor = new \Vaimo\ComposerPatches\Repository\Processor($logger);
    }

    public function applyPatches($devMode = false, array $filters = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $config = $this->configFactory->create();

        $patchesLoader = $this->loaderFactory->create($this->loaderComponents, $config, $devMode);
        $patchesApplier = $this->applierFactory->create($config, $filters);

        $this->repositoryProcessor->process($repository, $patchesLoader, $patchesApplier);
    }

    public function stripPatches($devMode = false)
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $sources = array(
            array(\Vaimo\ComposerPatches\Config::PATCHER_SOURCES => false)
        );

        $config = $this->configFactory->create($sources);

        $patchesLoader = $this->loaderFactory->create($this->loaderComponents, $config, $devMode);
        $patchesApplier = $this->applierFactory->create($config);

        $this->repositoryProcessor->process($repository, $patchesLoader, $patchesApplier);
    }
}
