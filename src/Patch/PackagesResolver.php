<?php
namespace Vaimo\ComposerPatches\Patch;

class PackagesResolver
{
    public function __construct()
    {
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
    }

    public function resolvePackagesToReinstall($packages, $patches)
    {
        $reinstallQueue = array();

        foreach ($packages as $package) {
            $packageName = $package->getName();
            $packagePatches = isset($patches[$packageName]) ? $patches[$packageName] : array();

            if (!$this->packageUtils->shouldReinstall($package, $packagePatches)) {
                continue;
            }

            $reinstallQueue[] = $packageName;
        }

        return $reinstallQueue;
    }
}