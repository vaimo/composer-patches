<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;
use Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface as ListLoader;
use Vaimo\ComposerPatches\Interfaces\PatchSourceListInterface;

class Loader
{
    /**
     * @var \Vaimo\ComposerPatches\Package\Collector
     */
    private $packagesCollector;
    
    /**
     * @var \Vaimo\ComposerPatches\Patch\Collector
     */
    private $patchesCollector;
    
    /**
     * @var ListLoader[]
     */
    private $processors;

    /**
     * @var PatchSourceListInterface[]
     */
    private $listSources;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packagesCollector
     * @param \Vaimo\ComposerPatches\Patch\Collector $patchesCollector
     * @param ListLoader[] $processors
     * @param PatchSourceListInterface[] $listSources
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packagesCollector,
        \Vaimo\ComposerPatches\Patch\Collector $patchesCollector,
        array $processors,
        array $listSources
    ) {
        $this->packagesCollector = $packagesCollector;
        $this->patchesCollector = $patchesCollector;
        $this->processors = $processors;
        $this->listSources = $listSources;
    }
    
    public function loadFromPackagesRepository(PackageRepository $repository)
    {
        $packages = $this->packagesCollector->collect($repository);

        $sources = array_reduce(
            $this->listSources, 
            function ($result, PatchSourceListInterface $listSource) use ($repository) {
                return array_merge($result, $listSource->getItems($repository));
            },
            array()
        );
        
        return array_reduce(
            $this->processors,
            function (array $patches, ListLoader $listLoader) use ($packages) {
                return $listLoader->process($patches, $packages);
            },
            $this->patchesCollector->collect(array_unique($sources))
        );
    }
}
