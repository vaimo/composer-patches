<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class PackageListUtils
{
    public function listToNameDictionary(array $packages)
    {
        return array_combine(
            array_map(function ($package) {
                return $package instanceof \Composer\Package\PackageInterface
                    ? $package->getName()
                    : $package['name'];
            }, $packages),
            $packages
        );
    }
}