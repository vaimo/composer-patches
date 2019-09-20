<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Factories;

/**
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
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
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $listResolver;

    /**
     * @var \Vaimo\ComposerPatches\Strategies\OutputStrategy
     */
    private $outputStrategy;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $appIO
     * @param \Vaimo\ComposerPatches\Factories\ConfigFactory $configFactory
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver
     * @param \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $appIO,
        \Vaimo\ComposerPatches\Factories\ConfigFactory $configFactory,
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver = null,
        \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy = null
    ) {
        $this->composer = $composer;
        $this->configFactory = $configFactory;
        $this->listResolver = $listResolver;
        $this->outputStrategy = $outputStrategy;
        
        $this->loaderComponents = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composer,
            $appIO
        );

        $logger = new \Vaimo\ComposerPatches\Logger($appIO);
        
        $this->configFactory = new Factories\ConfigFactory($composer);
        $this->loaderFactory = new Factories\PatchesLoaderFactory($composer);
        $this->applierFactory = new Factories\PatchesApplierFactory($composer, $logger);
        
        $this->repositoryProcessor = new \Vaimo\ComposerPatches\Repository\Processor($logger);
    }

    public function applyPatches($devMode = false)
    {
        return $this->applyPatchesWithConfig(
            $this->configFactory->create(),
            $devMode
        );
    }

    public function stripPatches($devMode = false)
    {
        $configSources = array(
            array(\Vaimo\ComposerPatches\Config::PATCHER_SOURCES => false)
        );
        
        $this->applyPatchesWithConfig(
            $this->configFactory->create($configSources),
            $devMode
        );
    }
    
    private function applyPatchesWithConfig(PluginConfig $config, $devMode = false)
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $patchesLoader = $this->loaderFactory->create($this->loaderComponents, $config, $devMode);
        
        $patchesApplier = $this->applierFactory->create(
            $config,
            $this->listResolver,
            $this->outputStrategy
        );

        return $this->repositoryProcessor->process($repository, $patchesLoader, $patchesApplier);
    }
}
