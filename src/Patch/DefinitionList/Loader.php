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
     * @var \Vaimo\ComposerPatches\Patch\SourcesResolver
     */
    private $sourcesResolver;

    /**
     * @var ListLoader[]
     */
    private $listLoaders;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packagesCollector
     * @param \Vaimo\ComposerPatches\Patch\Collector $patchesCollector
     * @param \Vaimo\ComposerPatches\Patch\SourcesResolver $sourcesResolver
     * @param ListLoader[] $listLoaders
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packagesCollector,
        \Vaimo\ComposerPatches\Patch\Collector $patchesCollector,
        \Vaimo\ComposerPatches\Patch\SourcesResolver $sourcesResolver,
        array $listLoaders
    ) {
        $this->packagesCollector = $packagesCollector;
        $this->patchesCollector = $patchesCollector;
        $this->sourcesResolver = $sourcesResolver;
        $this->listLoaders = $listLoaders;
    }

    public function loadFromPackagesRepository(PackageRepository $repository)
    {
        $packages = $this->packagesCollector->collect($repository);
        $sources = $this->sourcesResolver->resolvePackages($repository);

        $patches = $this->patchesCollector->collect($sources);
        
        $processedPatches = array_reduce(
            $this->listLoaders,
            function (array $patches, ListLoader $listLoader) use ($packages) {
                return $listLoader->process($patches, $packages);
            },
            $patches
        );

        return array_replace(
            array_fill_keys(array_keys($packages), array()),
            $processedPatches
        );
    }
}
