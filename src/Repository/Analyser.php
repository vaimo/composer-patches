<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

class Analyser
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface
     */
    private $packagesResolver;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface $packagesResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface $packagesResolver
    ) {
        $this->packagesResolver = $packagesResolver;

        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function determinePackageResets(array $repositoryState, array $patches)
    {
        $patchesByTarget = $this->patchListUtils->groupItemsByTarget($patches);
        $patchFootprints = $this->patchListUtils->createSimplifiedList($patchesByTarget);

        return $this->packagesResolver->resolve($patchFootprints, $repositoryState);
    }
}
