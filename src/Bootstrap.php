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
     * @var \Vaimo\ComposerPatches\Managers\PatcherStateManager
     */
    private $patcherStateManager;
    
    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composer, 
        \Composer\IO\IOInterface $io
    ) {
        $this->composer = $composer;
        
        $this->applierFactory = new PatchesApplierFactory($io);
        $this->repositoryFactory = new PatchesRepositoryFactory();

        $this->patcherStateManager = new \Vaimo\ComposerPatches\Managers\PatcherStateManager();
    }

    public function prepare()
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $this->patcherStateManager->extractAppliedPatchesInfo($repository);
    }

    public function apply($devMode = false, array $targets = array(), array $filters = array())
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        
        $this->patcherStateManager->restoreAppliedPatchesInfo($repository);

        $patchesApplier = $this->applierFactory->create(
            $this->composer, 
            $this->patcherStateManager
        );
        
        if (!$patchesApplier) {
            return null;
        }
        
        $repository = $this->repositoryFactory->create($this->composer, $devMode);
        
        $patchesApplier->apply($repository, $targets, $filters);
    }
    
    public function unload(array $targets = array())
    {
        $patchesApplier = $this->applierFactory->create(
            $this->composer, 
            $this->patcherStateManager
        );

        $config = $targets
            ? $this->composer->getPackage()->getExtra()
            : array(\Vaimo\ComposerPatches\Patch\Config::ENABLED => false);
        
        $repository = $this->repositoryFactory->create($this->composer, false, $config);

        $patchesApplier->apply($repository, $targets);
    }
}
