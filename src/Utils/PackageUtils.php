<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Composer\Package\PackageInterface;

use Vaimo\ComposerPatches\Config as PluginConfig;

class PackageUtils
{
    public function getRealPackage(PackageInterface $package)
    {
        while ($package instanceof \Composer\Package\AliasPackage) {
            $package = $package->getAliasOf();
        }

        return $package;
    }
    
    public function hasPatchChanges(PackageInterface $package, array $patches)
    {
        $extra = $package->getExtra();

        if (isset($extra[PluginConfig::APPLIED_FLAG])) {
            $appliedPatches = $extra[PluginConfig::APPLIED_FLAG];

            if ($appliedPatches === true) {
                return true;
            }

            return array_diff_assoc($appliedPatches, $patches)
                || array_diff_assoc($patches, $appliedPatches);
        }

        return (bool)count($patches);
    }

    public function resetAppliedPatches(PackageInterface $package, $replacement = null)
    {
        $patchesApplied = $this->getAppliedPatches($package);

        $extra = $package->getExtra();

        unset($extra[PluginConfig::APPLIED_FLAG]);

        if ($patchesApplied && $replacement !== null) {
            $extra[PluginConfig::APPLIED_FLAG] = $replacement;
        }

        if (method_exists($package, 'setExtra')) {
            $package->setExtra($extra);
        }

        return $patchesApplied;
    }

    public function getAppliedPatches(PackageInterface $package)
    {
        $extra = $package->getExtra();

        return isset($extra[PluginConfig::APPLIED_FLAG])
            ? $extra[PluginConfig::APPLIED_FLAG]
            : array();
    }

    public function getPrettyVersion($package)
    {
        while ($package instanceof \Composer\Package\AliasPackage) {
            $package = $package->getAliasOf();
        }
        
        if (method_exists($package, 'getPrettyVersion')) {
            return $package->getPrettyVersion();
        }
        
        if (method_exists($package, 'getPrettyConstraint')) {
            return $package->getPrettyConstraint();
        }

        return '';
    }
}
