<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repositories;

class PatchesRepository
{
    /**
     * @var \Composer\Package\RootPackageInterface
     */
    private $rootPackage;
    
    /**
     * @var \Composer\Repository\WritableRepositoryInterface
     */
    private $packagesRepository;
    
    /**
     * @var \Vaimo\ComposerPatches\Patch\Config
     */
    private $patchConfig;

    /**
     * @var \Vaimo\ComposerPatches\Package\Collector
     */
    private $packagesCollector;
    
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Loader
     */
    private $definitionListLoader;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @param \Composer\Package\RootPackageInterface $rootPackage
     * @param \Composer\Repository\WritableRepositoryInterface $packagesRepository
     * @param \Vaimo\ComposerPatches\Patch\Config $patchConfig
     * @param \Vaimo\ComposerPatches\Package\Collector $packagesCollector
     * @param \Vaimo\ComposerPatches\Patch\DefinitionList\Loader $definitionListLoader
     */
    public function __construct(
        \Composer\Package\RootPackageInterface $rootPackage,
        \Composer\Repository\WritableRepositoryInterface $packagesRepository,
        \Vaimo\ComposerPatches\Patch\Config $patchConfig,
        \Vaimo\ComposerPatches\Package\Collector $packagesCollector,
        \Vaimo\ComposerPatches\Patch\DefinitionList\Loader $definitionListLoader
    ) {
        $this->rootPackage = $rootPackage;
        $this->packagesRepository = $packagesRepository;
        $this->patchConfig = $patchConfig;
        $this->packagesCollector = $packagesCollector;
        $this->definitionListLoader = $definitionListLoader;
        
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
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
    
    public function getPatches($filters = array())
    {
        $filter = $filters ? $this->filterUtils->composeRegex($filters, '/') : false;
        $patches = array();

        if ($this->patchConfig->isPatchingEnabled()) {
            $patches = $this->definitionListLoader->loadFromPackagesRepository(
                $this->packagesRepository,
                !$this->patchConfig->isPackageScopeEnabled() 
                    ? array($this->rootPackage->getName()) 
                    : array()
            );
        }

        if ($filter) {
            $patches = $this->filterUtils->filterBySubItemKeys($patches, $filter);
        }

        return $patches;
    }
}
