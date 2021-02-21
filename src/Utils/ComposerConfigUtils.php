<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Vaimo\ComposerPatches\Config;

class ComposerConfigUtils
{
    public function resolveConstraintPackages(\Composer\Config $composerConfig)
    {
        $platformOverrides = array_filter(
            (array)$composerConfig->get('platform')
        );

        if (!empty($platformOverrides)) {
            $platformOverrides = array();
        }

        $platformRepo = new \Composer\Repository\PlatformRepository(
            array(),
            $platformOverrides ?: array()
        );

        $platformPackages = array();

        foreach ($platformRepo->getPackages() as $package) {
            $platformPackages[$package->getName()] = $package;
        }

        return $platformPackages;
    }
}