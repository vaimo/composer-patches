<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\PackageResolvers;

class FullResetResolver implements \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackagePatchDataUtils
     */
    private $patchDataUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;
    
    public function __construct()
    {
        $this->patchDataUtils = new \Vaimo\ComposerPatches\Utils\PackagePatchDataUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function resolve(array $patches, array $repositoryState)
    {
        $patchDataUtils = $this->patchDataUtils;
        
        return $this->patchListUtils->compareLists(
            $patches,
            $repositoryState,
            function ($packagePatches, $packageState) use ($patchDataUtils) {
                return $packagePatches
                    || $patchDataUtils->shouldReinstall($packageState, $packagePatches);
            }
        );
    }
}
