<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\PackageResolvers;

class MissingPatchesResolver implements \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackagePatchDataUtils
     */
    private $packagePatchDataUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    public function __construct()
    {
        $this->packagePatchDataUtils = new \Vaimo\ComposerPatches\Utils\PackagePatchDataUtils();
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }

    public function resolve(array $patches, array $repositoryState)
    {
        $matches = array();

        foreach ($repositoryState as $name => $packageState) {
            $packagePatches = isset($patches[$name]) ? $patches[$name] : array();
            
            if (!$this->packagePatchDataUtils->shouldReinstall($packageState, $packagePatches)) {
                continue;
            }

            $matches[] = $name;
        }

        return $matches;
    }
}
