<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class ValidatorComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        $validatedPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $validItems = array();

            foreach ($packagePatches as $data) {
                $path = $data[PatchDefinition::PATH];

                $uidSources = array(
                    file_exists($path) ? md5_file($path) : '',
                    md5($data['source']),
                    serialize($data[PatchDefinition::DEPENDS]),
                    serialize($data[PatchDefinition::TARGETS]),
                    serialize($data[PatchDefinition::CONFIG])
                );
                
                $data[PatchDefinition::HASH] = md5(implode('|', $uidSources));

                $validItems[] = $data;
            }

            if (!$validItems) {
                continue;
            }

            $validatedPatches[$patchTarget] = $validItems;
        }

        return $validatedPatches;
    }
}
