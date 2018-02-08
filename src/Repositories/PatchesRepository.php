<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repositories;

class PatchesRepository
{
    /**
     * @var \Composer\Repository\WritableRepositoryInterface
     */
    private $packagesRepository;
    
    /**
     * @var \Vaimo\ComposerPatches\Package\Collector
     */
    private $packagesCollector;
    
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Loader
     */
    private $definitionListLoader;

    /**
     * @param \Composer\Repository\WritableRepositoryInterface $packagesRepository
     * @param \Vaimo\ComposerPatches\Package\Collector $packagesCollector
     * @param \Vaimo\ComposerPatches\Patch\DefinitionList\Loader $definitionListLoader
     */
    public function __construct(
        \Composer\Repository\WritableRepositoryInterface $packagesRepository,
        \Vaimo\ComposerPatches\Package\Collector $packagesCollector,
        \Vaimo\ComposerPatches\Patch\DefinitionList\Loader $definitionListLoader
    ) {
        $this->packagesRepository = $packagesRepository;
        $this->packagesCollector = $packagesCollector;
        $this->definitionListLoader = $definitionListLoader;
    }
    
    public function getSource()
    {
        return $this->packagesRepository;
    }
    
    public function write()
    {
        return $this->packagesRepository->write();
    }
    
    public function getTargets()
    {
        return $this->packagesCollector->collect($this->packagesRepository);
    }
    
    public function getPatches()
    {
        return $this->definitionListLoader->loadFromPackagesRepository($this->packagesRepository);
    }
}
