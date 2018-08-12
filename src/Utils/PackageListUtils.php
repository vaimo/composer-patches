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
            array_map(function (\Composer\Package\PackageInterface $package) {
                return $package->getName();
            }, $packages),
            $packages
        );
    }
}