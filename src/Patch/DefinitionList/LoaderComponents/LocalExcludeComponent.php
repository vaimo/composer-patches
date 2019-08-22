<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class LocalExcludeComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if (!$patchData[PatchDefinition::SKIP]) {
                    continue;
                }

                $patchData = false;
            }
            
            unset($patchData);

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
