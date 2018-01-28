<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;
use Vaimo\ComposerPatches\Config as PluginConfig;

class AppliedPatchesManager
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    public function __construct()
    {
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }

    public function registerAppliedPatches(WritableRepositoryInterface $repository, array $patches)
    {
        $packages = array();
        
        foreach ($patches as $source => $patchInfo) {
            foreach ($patchInfo['targets'] as $target) {
                $package = $this->packageUtils->getRealPackage(
                    $repository->findPackage($target, '*')
                );

                $package->setExtra(
                    array_replace_recursive($package->getExtra(), array(
                        PluginConfig::APPLIED_FLAG => array(
                            $source => $patchInfo['label']
                        )
                    ))
                );
                
                $packages[] = $package;
            }
        }

        foreach ($packages as $package) {
            $extra = $package->getExtra();

            ksort($extra[PluginConfig::APPLIED_FLAG]);
            
            $package->setExtra($extra);
        }
    }
}
