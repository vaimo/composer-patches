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

    public function validate($patches, $vendorDir)
    {
        $validatedPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $validItems = array();

            foreach ($packagePatches as $patch) {
                $absolutePatchPath = $vendorDir . '/' . $patch['source'];

                $patch['md5'] = md5(implode('|', array(
                    file_exists($absolutePatchPath) ? md5_file($absolutePatchPath) : md5($patch['source']),
                    $patch['version']
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
