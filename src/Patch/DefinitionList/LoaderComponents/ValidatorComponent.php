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
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        $validatedPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $validItems = array();

            foreach ($packagePatches as $patch) {
                $relativePath = $patch[PatchDefinition::SOURCE];
                $absolutePatchPath = $vendorRoot . DIRECTORY_SEPARATOR . $relativePath;
                
                $patchPath = file_exists($absolutePatchPath)
                    ? $absolutePatchPath
                    : $relativePath;

                $patch[PatchDefinition::HASH] = md5(implode('|', array(
                    file_exists($patchPath) ? md5_file($patchPath) : md5($patchPath),
                    serialize($patch[PatchDefinition::DEPENDS]),
                    serialize($patch[PatchDefinition::TARGETS]),
                    serialize($patch[PatchDefinition::CONFIG])
                )));

                $validItems[] = $patch;
            }

            if (!$validItems) {
                continue;
            }

            $validatedPatches[$patchTarget] = $validItems;
        }

        return $validatedPatches;
    }
}
