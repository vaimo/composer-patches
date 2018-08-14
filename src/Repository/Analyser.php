<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Composer\Repository\WritableRepositoryInterface;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

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
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

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

    public function determinePackageResets(WritableRepositoryInterface $repository, array $patches)
    {
        $packages = $this->packageCollector->collect($repository);
        $patchFootprints = $this->patchListUtils->createSimplifiedList($patches);

        return $this->packagesResolver->resolve($patchFootprints, $packages);
    }
}
