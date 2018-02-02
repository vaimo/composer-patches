<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class BundleComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Composer\Package\RootPackageInterface
     */
    private $rootPackage;

    /**
     * @param \Composer\Package\RootPackageInterface $rootPackage
     */
    public function __construct(
        \Composer\Package\RootPackageInterface $rootPackage
    ) {
        $this->rootPackage = $rootPackage;
    }
    
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        $rootName = $this->rootPackage->getName();
        
        if (isset($patches[PatchDefinition::BUNDLE_TARGET])) {
            if (!isset($patches[$rootName])) {
                $patches[$rootName] = array();
            }

            $patches[$rootName] = array_merge(
                $patches[$rootName],
                $patches[PatchDefinition::BUNDLE_TARGET]
            );

            unset($patches[PatchDefinition::BUNDLE_TARGET]);
        }
        
        return $patches;
    }
}
