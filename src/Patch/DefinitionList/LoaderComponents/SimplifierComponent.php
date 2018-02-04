<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SimplifierComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        $allPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $allPatches[$patchTarget] = array();

            foreach ($packagePatches as $info) {
                $allPatches[$patchTarget][$info[PatchDefinition::SOURCE]] = array(
                    PatchDefinition::URL => $info[PatchDefinition::URL],
                    PatchDefinition::PATH => $info[PatchDefinition::PATH],
                    PatchDefinition::OWNER => $info[PatchDefinition::OWNER],
                    PatchDefinition::TARGETS => $info[PatchDefinition::TARGETS],
                    PatchDefinition::HASH => isset($info[PatchDefinition::HASH]) ? $info[PatchDefinition::HASH] : '',
                    PatchDefinition::LABEL => $info[PatchDefinition::LABEL],
                    PatchDefinition::CONFIG => $info[PatchDefinition::CONFIG]
                );
            }
        }

        return array_filter($allPatches);
    }
}
