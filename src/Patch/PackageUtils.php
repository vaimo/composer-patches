<?php
namespace Vaimo\ComposerPatches\Patch;

use Composer\Package\PackageInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

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

        if ($applied === true) {
            return true;
        }

        return array_diff_assoc($applied, $patches) || array_diff_assoc($patches, $applied);
    }

    public function hasPatchChanges(PackageInterface $package, array $patches)
    {
        $extra = $package->getExtra();

        if (isset($extra['patches_applied'])) {
            $appliedPatches = $extra['patches_applied'];

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

        $patchesApplied = isset($extra['patches_applied']) ? $extra['patches_applied'] : [];

        unset($extra['patches_applied']);

        if ($replacement !== null && $patchesApplied) {
            $extra['patches_applied'] = $replacement;
        }

        $package->setExtra($extra);

        return $patchesApplied;
    }

    public function registerPatch(PackageInterface $package, $path, $description)
    {
        $extra = $package->getExtra();

        if (!isset($extra['patches_applied']) || !is_array($extra['patches_applied'])) {
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

    public function groupPatchesByTarget($patches)
    {
        $patchesByTarget = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                foreach ($patchInfo[PatchDefinition::TARGETS] as $target) {
                    if (!isset($patchesByTarget[$target])) {
                        $patchesByTarget[$target] = array();
                    }

                    $patchesByTarget[$target][$patchPath] = $patchInfo['label'];
                }
            }
        }

        return $patchesByTarget;
    }
}
