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
        ) ?: array();
    }

    public function extractDataFromExtra(array $packages, $key = null, $default = null)
    {
        $defaultType = gettype($default);

        return array_map(
            function (\Composer\Package\PackageInterface $package) use ($key, $default, $defaultType) {
                $extra = $package->getExtra();

                if (!$key) {
                    return $extra;
                }

                return isset($extra[$key])
                    ? (gettype($extra[$key]) === $defaultType || $default === null) ? $extra[$key] : $default
                    : $default;
            },
            $packages
        );
    }
}
