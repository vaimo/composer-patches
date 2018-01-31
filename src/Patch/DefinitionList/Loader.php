<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList;

use Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface;
use Composer\Repository\WritableRepositoryInterface as PackageRepository;

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
     * @var string
     */
    private $vendorRoot;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packagesCollector
     * @param \Vaimo\ComposerPatches\Patch\Collector $patchesCollector
     * @param DefinitionListLoaderComponentInterface[] $processors
     * @param string $vendorRoot
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packagesCollector,
        \Vaimo\ComposerPatches\Patch\Collector $patchesCollector,
        array $processors,
        $vendorRoot
    ) {
        $this->packagesCollector = $packagesCollector;
        $this->patchesCollector = $patchesCollector;
        $this->processors = $processors;
        $this->vendorRoot = $vendorRoot;
    }
    
    public function loadFromPackagesRepository(PackageRepository $repository, array $filter = array())
    {
        $packages = $this->packagesCollector->collect($repository);

        $sources = $filter ? array_intersect_key($packages, array_flip($filter)) : $packages;
        
        return array_reduce(
            $this->processors,
            function (array $patches, DefinitionListLoaderComponentInterface $processor) use ($packages) {
                return $processor->process($patches, $packages, $this->vendorRoot);
            },
            $this->patchesCollector->collect($sources)
        );
    }
}
