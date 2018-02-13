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
    
    public function shouldReinstall(PackageInterface $package, array $patches)
    {
        $extra = $package->getExtra();

        if (!isset($extra[PluginConfig::APPLIED_FLAG])) {
            return false;
        }

        if (!$applied = $extra[PluginConfig::APPLIED_FLAG]) {
            return false;
        }

        if ($applied === true) {
            return true;
        }

        return array_diff_assoc($applied, $patches) || array_diff_assoc($patches, $applied);
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
        $extra = $package->getExtra();

        $patchesApplied = isset($extra[PluginConfig::APPLIED_FLAG]) 
            ? $extra[PluginConfig::APPLIED_FLAG] 
            : array();

        unset($extra[PluginConfig::APPLIED_FLAG]);

        if ($patchesApplied && $replacement !== null) {
            $extra[PluginConfig::APPLIED_FLAG] = $replacement;
        }

        $package->setExtra($extra);

        return $patchesApplied;
    }
}
