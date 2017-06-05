<?php
namespace Vaimo\ComposerPatches\Patch;

class DefinitionsProcessor
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

    public function includeCheckSums($patches, $vendorDir)
    {
        foreach ($patches as $patchTarget => &$packagePatches) {
            $allPatches[$patchTarget] = array();

            foreach ($packagePatches as &$patchInfo) {
                $source = $patchInfo['source'];
                $absolutePatchPath = $vendorDir . '/' . $source;

                if (file_exists($absolutePatchPath)) {
                    $source = $absolutePatchPath;
                }

                $patchInfo['md5'] = md5(implode('|', array(
                    md5_file($source),
                    $patchInfo['version']
                )));
            }
        }

        return $patches;
    }

    public function flatten($patches)
    {
        $allPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $allPatches[$patchTarget] = array();

            foreach ($packagePatches as $patchInfo) {
                $allPatches[$patchTarget][$patchInfo['source']] =
                    $patchInfo['label'] . ', md5:' . $patchInfo['md5'];
            }
        }

        return $allPatches;
    }
}
