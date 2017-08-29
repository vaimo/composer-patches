<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class Simplifier implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
{
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        $allPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $allPatches[$patchTarget] = array();

            foreach ($packagePatches as $info) {
                $allPatches[$patchTarget][$info[PatchDefinition::SOURCE]] = array(
                    'targets' => $info[PatchDefinition::TARGETS],
                    'label' => $info[PatchDefinition::LABEL] . (
                        isset($info[PatchDefinition::HASH])
                            ? ', md5:' . $info[PatchDefinition::HASH]
                            : ''
                        )
                );
            }
        }

        return array_filter($allPatches);
    }
}
