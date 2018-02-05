<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class MergerComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        $allPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $allPatches[$patchTarget] = array();

            foreach ($packagePatches as $info) {
                $allPatches[$patchTarget][$info[PatchDefinition::SOURCE]] = $info;
            }
        }

        return array_filter($allPatches);
    }
}
