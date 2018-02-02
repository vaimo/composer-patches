<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;
use Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface;
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
     * @var DefinitionListLoaderComponentInterface[]
     */
    private $processors;

    /**
     * @var PatchSourceListInterface[]
     */
    private $listSources;

    /**
     * @var string
     */
    private $vendorRoot;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packagesCollector
     * @param \Vaimo\ComposerPatches\Patch\Collector $patchesCollector
     * @param DefinitionListLoaderComponentInterface[] $processors
     * @param PatchSourceListInterface[] $listSources
     * @param string $vendorRoot
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packagesCollector,
        \Vaimo\ComposerPatches\Patch\Collector $patchesCollector,
        array $processors,
        array $listSources,
        $vendorRoot
    ) {
        $this->packagesCollector = $packagesCollector;
        $this->patchesCollector = $patchesCollector;
        $this->processors = $processors;
        $this->listSources = $listSources;
        $this->vendorRoot = $vendorRoot;
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
            function (array $patches, DefinitionListLoaderComponentInterface $processor) use ($packages) {
                return $processor->process($patches, $packages, $this->vendorRoot);
            },
            $this->patchesCollector->collect(array_unique($sources))
        );
    }
}
