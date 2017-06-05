<?php
namespace Vaimo\ComposerPatches\Patch;

class DefinitionsProcessor
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionNormalizer
     */
    protected $definitionNormalizer;

    public function __construct()
    {
        $this->definitionNormalizer = new \Vaimo\ComposerPatches\Patch\DefinitionNormalizer();
    }

    public function normalizeDefinitions($patches)
    {
        $patchesPerPackage = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $normalizedPatches = array();

            foreach ($packagePatches as $label => $data) {
                $normalizedPatches[] = $this->definitionNormalizer->process($label, $data);
            }

            if (!$validPatches = array_filter($normalizedPatches)) {
                continue;
            }

            $patchesPerPackage[$patchTarget] = $validPatches;
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

            foreach ($packagePatches as $info) {
                $allPatches[$patchTarget][$info['source']] = $info['label']
                    . (isset($info['md5']) ? ', md5:' . $info['md5'] : '');
            }
        }

        return $allPatches;
    }
}
