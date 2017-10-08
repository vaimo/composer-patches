<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;

class AppliedPatchesManager
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\RepositoryUtils
     */
    private $repositoryUtils;

    /**
     * @var array
     */
    private $appliedPatches = array();

    public function __construct()
    {
        $this->repositoryUtils = new \Vaimo\ComposerPatches\Utils\RepositoryUtils();
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }

    public function extractAppliedPatchesInfo(WritableRepositoryInterface $repository)
    {
        $this->appliedPatches = array();
        
        foreach ($this->repositoryUtils->getPackagesByName($repository) as $name => $package) {
            if (isset($this->appliedPatches[$name])) {
                continue;
            }

            $this->appliedPatches[$name] = $this->packageUtils->resetAppliedPatches($package, true);
        }
    }

    public function restoreAppliedPatchesInfo(WritableRepositoryInterface $repository)
    {
        foreach ($this->repositoryUtils->getPackagesByName($repository) as $name => $package) {
            if (!isset($this->appliedPatches[$name]) || !$this->appliedPatches[$name]) {
                continue;
            }
            
            $this->packageUtils->resetAppliedPatches($package, $this->appliedPatches[$name]);
        }
    }
}
