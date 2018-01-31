<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Composer\Repository\WritableRepositoryInterface;

class Analyser
{
    /**
     * @var \Vaimo\ComposerPatches\Package\Collector
     */
    private $packageCollector;
    
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface
     */
    private $packagesResolver;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packageCollector
     * @param \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface $packagesResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packageCollector,
        \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface $packagesResolver
    ) {
        $this->packageCollector = $packageCollector;
        $this->packagesResolver = $packagesResolver;

        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }
    
    public function determinePackageResets(
        WritableRepositoryInterface $repository, array $patches, $targets = array()
    ) {
        $packages = $this->packageCollector->collect($repository);
        $patchQueue = $this->patchListUtils->createSimplifiedList($patches);
        
        $resetFlags = array_fill_keys(
            $this->packagesResolver->resolve($patchQueue, $packages), 
            false
        );
        
        if (!$targets) {
            return array_keys($resetFlags);
        }
        
        return array_keys(
            array_intersect_key($resetFlags, array_flip($targets))
        );
    }
}
