<?php
namespace Vaimo\ComposerPatches\Utils;

use Composer\Repository\WritableRepositoryInterface;

class RepositoryUtils
{
    public function getPackagesByName(WritableRepositoryInterface $repository)
    {
        $packages = array();
        
        foreach ($repository->getPackages() as $package) {
            $packages[$package->getName()] = $package instanceof \Composer\Package\AliasPackage 
                ? $package->getAliasOf() 
                : $package;
        }
        
        return $packages;
    }
}
