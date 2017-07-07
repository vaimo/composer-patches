<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;

class AppliedPatchesManager
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\PackageUtils
     */
    private $packageUtils;

    /**
     * @var array
     */
    private $appliedPatches = array();

    public function __construct()
    {
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
    }

    public function extractAppliedPatchesInfo(WritableRepositoryInterface $repository)
    {
        $this->appliedPatches = array();

        foreach ($repository->getPackages() as $package) {
            if ($package instanceof \Composer\Package\AliasPackage) {
                $package = $package->getAliasOf();
            }

            if (isset($this->appliedPatches[$package->getName()])) {
                continue;
            }

            if (!$patches = $this->packageUtils->resetAppliedPatches($package, true)) {
                continue;
            }

            $this->appliedPatches[$package->getName()] = $patches;
        }
    }

    public function restoreAppliedPatchesInfo(WritableRepositoryInterface $repository)
    {
        foreach ($repository->getPackages() as $package) {
            $packageName = $package->getName();

            if ($package instanceof \Composer\Package\AliasPackage) {
                $package = $package->getAliasOf();
            }

            $extra = $package->getExtra();

            if (!isset($this->appliedPatches[$packageName])) {
                continue;
            }

            if (!$extra['patches_applied'] = $this->appliedPatches[$packageName]) {
                continue;
            }

            $package->setExtra($extra);
        }
    }
}
