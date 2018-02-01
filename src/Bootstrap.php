<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

use Vaimo\ComposerPatches\Factories\PatchesApplierFactory;
use Vaimo\ComposerPatches\Factories\PatchesRepositoryFactory;

class Bootstrap
{
    /**
     * @var \Composer\Composer
     */
    private $composer;
    
    /**
     * @var PatchesApplierFactory
     */
    private $applierFactory;

    /**
     * @var PatchesRepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @var array
     */
    private $config;
    
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
        
        $this->applierFactory = new PatchesApplierFactory($io);
        $this->repositoryFactory = new PatchesRepositoryFactory();

        $this->patcherStateManager = new \Vaimo\ComposerPatches\Managers\PatcherStateManager();
    }

    public function applyPatches($devMode = false, array $targets = array(), array $filters = array())
    {
        $patchesApplier = $this->applierFactory->create($this->composer, $this->patcherStateManager);
        
        if (!$patchesApplier) {
            return null;
        }

        $config = array_replace(
            $this->composer->getPackage()->getExtra(),
            $this->config
        );
        
        $repository = $this->repositoryFactory->create($this->composer, $config, $devMode);
        
        $patchesApplier->apply($repository, $targets, $filters);
    }
    
    public function stripPatches(array $targets = array())
    {
        $patchesApplier = $this->applierFactory->create(
            $this->composer, 
            $this->patcherStateManager
        );

        $config = array_replace(
            $this->composer->getPackage()->getExtra(),
            $this->config,
            array(\Vaimo\ComposerPatches\Patch\Config::ENABLED => false)
        );
        
        $repository = $this->repositoryFactory->create($this->composer, $config);

        $patchesApplier->apply($repository, $targets);
    }
}
