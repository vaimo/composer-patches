<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Sources;

use Composer\Repository\WritableRepositoryInterface;
use Composer\Package\PackageInterface;

class VendorSource implements \Vaimo\ComposerPatches\Interfaces\PatchSourceListInterface
{
    /**
     * @var array
     */
    private $vendors;

    /**
     * @param array $vendors
     */
    public function __construct(
        array $vendors = array()
    ) {
        $this->vendors = $vendors;
    }

    public function getItems(WritableRepositoryInterface $repository)
    {
        $packages = $repository->getPackages();
        
        if (!$this->vendors) {
            return $packages; 
        }

        $allowedVendors = array_fill_keys($this->vendors, true);
        return array_filter($packages, function (PackageInterface $package) use ($allowedVendors) {
            return isset($allowedVendors[strtok($package->getName(), '/')]);
        });
    }
}
