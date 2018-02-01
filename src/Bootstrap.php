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
    }

    public function applyPatches($devMode = false, array $targets = array(), array $filters = array())
    {
        $config = array_replace(
            $this->composer->getPackage()->getExtra(),
            $this->config
        );
        
        $patchesApplier = $this->applierFactory->create($this->composer, $config);
        
        if (!$patchesApplier) {
            return null;
        }
        
        $repository = $this->repositoryFactory->create($this->composer, $config, $devMode);
        
        $patchesApplier->apply($repository, $targets, $filters);
    }
    
    public function stripPatches(array $targets = array())
    {
        $config = array_replace(
            $this->composer->getPackage()->getExtra(),
            $this->config,
            array(\Vaimo\ComposerPatches\Patch\Config::ENABLED => false)
        );
        
        $patchesApplier = $this->applierFactory->create($this->composer, $config);
        
        $repository = $this->repositoryFactory->create($this->composer, $config);

        $patchesApplier->apply($repository, $targets);
    }
}
