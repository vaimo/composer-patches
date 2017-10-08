<?php
namespace Vaimo\ComposerPatches\Utils;

use Composer\Package\PackageInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class PackageUtils
{
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

            if (!array_diff_assoc($appliedPatches, $patches)
                && !array_diff_assoc($patches, $appliedPatches)
            ) {
                return false;
            }
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

    public function registerPatch(PackageInterface $package, $path, $description)
    {
        $extra = $package->getExtra();

        if (!isset($extra[PluginConfig::APPLIED_FLAG]) || !is_array($extra[PluginConfig::APPLIED_FLAG])) {
            $extra[PluginConfig::APPLIED_FLAG] = array();
        }

        $extra[PluginConfig::APPLIED_FLAG][$path] = $description;

        $package->setExtra($extra);
    }

    public function sortPatches(PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (isset($extra[PluginConfig::APPLIED_FLAG])) {
            ksort($extra[PluginConfig::APPLIED_FLAG]);
        }

        $package->setExtra($extra);
    }

    public function groupPatchesByTarget(array $patches)
    {
        $patchesByTarget = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                foreach ($patchInfo[PatchDefinition::TARGETS] as $target) {
                    if (!isset($patchesByTarget[$target])) {
                        $patchesByTarget[$target] = array();
                    }

                    $patchesByTarget[$target][$patchPath] = $patchInfo[PatchDefinition::LABEL];
                }
            }
        }

        return $patchesByTarget;
    }
    
    public function extractPackageFromVendorPath($path)
    {
        $package = implode('/', array_slice(explode('/', $path), 0, 2));
        $path = trim(substr($path, strlen($package)), '/');

        return array($package, $path);
    }
}
