<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

use Vaimo\ComposerPatches\Factories\PatchesApplierFactory;
use Vaimo\ComposerPatches\Factories\PatchesRepositoryFactory;
use Vaimo\ComposerPatches\Factories\ConfigFactory;

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
     * @var PatchesApplierFactory
     */
    private $applierFactory;

    /**
     * @var PatchesRepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @var Factories\ConfigFactory
     */
    private $configFactory;
    
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
        $this->repositoryFactory = new PatchesRepositoryFactory($io);
        $this->configFactory = new ConfigFactory();
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
    
    public function stripPatches(array $targets = array())
    {
        $config = $this->configFactory->create(
            $this->composer,
            array($this->config, array(\Vaimo\ComposerPatches\Config::PATCHER_SOURCES => array()))
        );
        
        $patchesApplier = $this->applierFactory->create($this->composer, $config);
        $repository = $this->repositoryFactory->create($this->composer, $config);

        $patchesApplier->apply($repository, $targets);
    }
}
