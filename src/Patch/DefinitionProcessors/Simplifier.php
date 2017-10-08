<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class Simplifier implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
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
                    PatchDefinition::TARGETS => $info[PatchDefinition::TARGETS],
                    PatchDefinition::LABEL => $info[PatchDefinition::LABEL] . (
                        isset($info[PatchDefinition::HASH])
                            ? sprintf(', %s:%s', PatchDefinition::HASH, $info[PatchDefinition::HASH])
                            : ''
                        )
                );
            }
        }

        return array_filter($allPatches);
    }
}
