<?php
namespace Vaimo\ComposerPatches\Patch;

class DefinitionParser
{
    public function normalize($patches)
    {
        $patchesPerPackage = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $normalizedPatches = array();

            foreach ($packagePatches as $label => $data) {
                if (!is_array($data)) {
                    $data = array(
                        'source' => (string)$data
                    );
                }

                if (!isset($data['url']) && !isset($data['source'])) {
                    continue;
                }

                $normalizedPatches[] = array(
                    'source' => isset($data['url']) ? $data['url'] : $data['source'],
                    'label' => isset($data['label']) ? $data['label'] : $label,
                    'version' => isset($data['version']) ? $data['version'] : false
                );
            }

            if (!$normalizedPatches) {
                continue;
            }

            $patchesPerPackage[$patchTarget] = $normalizedPatches;
        }

        return $patchesPerPackage;
    }

    public function simplify($patches)
    {
        $allPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $allPatches[$patchTarget] = array();

            foreach ($packagePatches as $patchData) {
                $allPatches[$patchTarget][$patchData['source']] = $patchData['label'];
            }
        }

        return $allPatches;
    }
}
