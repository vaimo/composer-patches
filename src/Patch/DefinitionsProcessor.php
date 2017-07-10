<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class DefinitionsProcessor
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionNormalizer
     */
    private $definitionNormalizer;

    public function __construct()
    {
        $this->definitionNormalizer = new \Vaimo\ComposerPatches\Patch\DefinitionNormalizer();
    }

    public function normalizeDefinitions(array $patches)
    {
        $patchesPerPackage = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $normalizedPatches = array();

            foreach ($packagePatches as $label => $data) {
                $normalizedPatches[] = $this->definitionNormalizer->process($patchTarget, $label, $data);
            }

            if (!$validPatches = array_filter($normalizedPatches)) {
                continue;
            }

            $patchesPerPackage[$patchTarget] = $validPatches;
        }

        return $patchesPerPackage;
    }

    public function validate(array $patches, $vendorDir)
    {
        $validatedPatches = array();

        foreach ($patches as $patchTarget => $packagePatches) {
            $validItems = array();

            foreach ($packagePatches as $patch) {
                $relativePath = $patch[PatchDefinition::SOURCE];
                $absolutePatchPath = $vendorDir . '/' . $relativePath;

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

    public function simplify(array $patches)
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
