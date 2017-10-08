<?php
namespace Vaimo\ComposerPatches\Patch\PackageResolvers;

class MissingPatchesResolver implements \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\PackageUtils
     */
    private $packageUtils;

    public function __construct()
    {
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
    }
    
    public function resolve(array $patches, array $packages)
    {
        $matches = array();

        foreach ($packages as $packageName => $package) {
            $packagePatches = isset($patches[$packageName]) ? $patches[$packageName] : array();
            
            if (!$this->packageUtils->shouldReinstall($package, $packagePatches)) {
                continue;
            }

            $matches[] = $packageName;
        }
        
        return $matches;
    }
}
