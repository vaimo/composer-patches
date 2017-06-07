<?php
namespace Vaimo\ComposerPatches\Patch;

use Composer\Package\PackageInterface;

class PackageUtils
{
    public function shouldReinstall(PackageInterface $package, array $patches)
    {
        $extra = $package->getExtra();

        if (!isset($extra['patches_applied'])) {
            return false;
        }

        if (!$applied = $extra['patches_applied']) {
            return false;
        }

        if (!array_diff_assoc($applied, $patches) && !array_diff_assoc($patches, $applied)) {
            return false;
        }

        return true;
    }

    public function hasPatchChanges(PackageInterface $package, array $patches)
    {
        $extra = $package->getExtra();

        if (isset($extra['patches_applied'])) {
            $appliedPatches = $extra['patches_applied'];

            if (!array_diff_assoc($appliedPatches, $patches)
                && !array_diff_assoc($patches, $appliedPatches)
            ) {
                return false;
            }
        }

        return (bool)count($patches);
    }

    public function resetAppliedPatches(PackageInterface $package)
    {
        $extra = $package->getExtra();

        unset($extra['patches_applied']);

        $package->setExtra($extra);

        return true;
    }

    public function registerPatch(PackageInterface $package, $path, $description)
    {
        $extra = $package->getExtra();

        if (!isset($extra['patches_applied'])) {
            $extra['patches_applied'] = array();
        }

        $extra['patches_applied'][$path] = $description;

        $package->setExtra($extra);
    }

    public function sortPatches(PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (isset($extra['patches_applied'])) {
            ksort($extra['patches_applied']);
        }

        $package->setExtra($extra);
    }
}
