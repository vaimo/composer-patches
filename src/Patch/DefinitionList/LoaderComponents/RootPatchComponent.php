<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class RootPatchComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Composer\Package\PackageInterface
     */
    private $package;

    /**
     * @param \Composer\Package\PackageInterface $package
     */
    public function __construct(
        \Composer\Package\PackageInterface $package
    ) {
        $this->package = $package;
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        $packageName = $this->package->getName();
        
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if (!$patchData[PatchDefinition::LOCAL]) {
                    continue;
                }

                if ($packageName === $patchData[PatchDefinition::OWNER]) {
                    continue;
                }
                
                $patchData = false;
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
