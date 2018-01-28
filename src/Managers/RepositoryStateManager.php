<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;

class RepositoryStateManager
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;
    
    /**
     * @var array
     */
    private $appliedPatches = array();

    public function __construct()
    {
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }
    
    public function extractAppliedPatchesInfo(WritableRepositoryInterface $repository)
    {
        $this->appliedPatches = array();

        foreach ($repository->getPackages() as $package) {
            $package = $this->packageUtils->getRealPackage($package);
            $name = $package->getName();
            
            if (isset($this->appliedPatches[$name])) {
                continue;
            }

            $this->appliedPatches[$name] = $this->packageUtils->resetAppliedPatches($package, true);
        }
    }

    public function restoreAppliedPatchesInfo(WritableRepositoryInterface $repository)
    {
        foreach ($repository->getPackages() as $package) {
            $name = $package->getName();
            
            if (!isset($this->appliedPatches[$name]) || !$this->appliedPatches[$name]) {
                continue;
            }

            $this->packageUtils->resetAppliedPatches($package, $this->appliedPatches[$name]);
        }
    }
}
