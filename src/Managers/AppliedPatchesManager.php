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
            if (isset($this->appliedPatches[$package->getName()])) {
                continue;
            }

            $this->appliedPatches[$package->getName()] = $this->packageUtils->resetAppliedPatches($package);
        }
    }

    public function restoreAppliedPatchesInfo(WritableRepositoryInterface $repository)
    {
        foreach ($repository->getPackages() as $package) {
            $packageName = $package->getName();
            $extra = $package->getExtra();

            $extra['patches_applied'] = array_merge(
                isset($extra['patches_applied']) ? $extra['patches_applied'] : array(),
                isset($this->appliedPatches[$packageName]) ? $this->appliedPatches[$packageName] : array()
            );

            if (!$extra['patches_applied']) {
                continue;
            }

            $package->setExtra($extra);
        }
    }
}
