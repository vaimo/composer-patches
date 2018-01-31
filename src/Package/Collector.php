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
     * @var \Composer\Package\RootPackageInterface
     */
    private $rootPackage;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @param \Composer\Package\RootPackageInterface $rootPackage
     */
    public function __construct(
        \Composer\Package\RootPackageInterface $rootPackage
    ) {
        $this->rootPackage = $rootPackage;
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }
    
    public function collect(WritableRepositoryInterface $repository)
    {
        $targets = array();

        $packages = $repository->getPackages();
        
        foreach ($packages as $package) {
            $targets[$package->getName()] = $this->packageUtils->getRealPackage($package);
        }

        $targets[$this->rootPackage->getName()] = $this->rootPackage;

        return $targets;
    }
}
