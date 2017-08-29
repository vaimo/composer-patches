<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class Validator implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
{
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        $validatedPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $validItems = array();

            foreach ($packagePatches as $patch) {
                $relativePath = $patch[PatchDefinition::SOURCE];
                $absolutePatchPath = $vendorRoot . '/' . $relativePath;

                $patchPath = file_exists($absolutePatchPath)
                    ? $absolutePatchPath
                    : $relativePath;

                $patch[PatchDefinition::HASH] = md5(implode('|', array(
                    file_exists($patchPath) ? md5_file($patchPath) : md5($patchPath),
                    serialize($patch[PatchDefinition::DEPENDS]),
                    serialize($patch[PatchDefinition::TARGETS]),
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
