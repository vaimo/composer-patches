<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\PackageResolvers;

class FullResetResolver implements \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    public function __construct()
    {
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }

    public function resolve(array $patches, array $packages)
    {
        $matches = array();

        foreach ($packages as $name => $package) {
            if (!$this->packageUtils->shouldReinstall($package, array()) && !isset($patches[$name])) {
                continue;
            }

            $matches[$name] = $this->packageUtils->getAppliedPatches($package);
        }

        return $matches;
    }
}
