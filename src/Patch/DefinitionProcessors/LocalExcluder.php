<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class LocalExcluder implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
{
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        foreach ($patches as $targetPackageName => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if (!$patchData[PatchDefinition::SKIP]) {
                    continue;
                }

                $patchData = false;
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
