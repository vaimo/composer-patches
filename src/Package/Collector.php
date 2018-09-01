<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\Repository\WritableRepositoryInterface;

class Collector
{
    /**
     * @var array|\Composer\Package\PackageInterface[]
     */
    private $extraPackages;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils 
     */
    private $packageUtils;

    /**
     * @param \Composer\Package\PackageInterface[] $extraPackages
     */
    public function __construct(
        array $extraPackages = array()
    ) {
        $this->extraPackages = $extraPackages;
        
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }
    
    public function collect(WritableRepositoryInterface $repository)
    {
        $packages = $this->extraPackages;
        
        foreach ($repository->getPackages() as $package) {
            $packages[] = $this->packageUtils->getRealPackage($package);
        }

        return array_combine(
            array_map(function (\Composer\Package\PackageInterface $package) {
                return $package->getName();
            }, $packages),
            $packages
        );
    }
}
