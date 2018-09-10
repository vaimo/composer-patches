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
    
    public function extractExtraData(array $packages, $key = null, $default = array())
    {
        return array_map(function (\Composer\Package\PackageInterface $package) use ($key, $default) {
            $extra = $package->getExtra();
            
            if (!$key) {
                return $extra;
            }

            return isset($extra[$key])
                ? $extra[$key]
                : $default;
        }, $packages);
    }
}
