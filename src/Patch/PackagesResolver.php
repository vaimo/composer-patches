<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PackagesResolver
{
    /**
     * @var PackageUtils
     */
    private $packageUtils;

    public function __construct()
    {
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
    }

    public function resolvePackagesToReinstall($packages, $groupedPatches)
    {
        $reinstallQueue = array();

        foreach ($packages as $packageName => $package) {
            $packagePatches = isset($groupedPatches[$packageName]) ? $groupedPatches[$packageName] : array();

            if (!$this->packageUtils->shouldReinstall($package, $packagePatches)) {
                continue;
            }

            $reinstallQueue[] = $packageName;
        }

        return $reinstallQueue;
    }
}
