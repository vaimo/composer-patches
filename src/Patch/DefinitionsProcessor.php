<?php
namespace Vaimo\ComposerPatches\Patch;

class DefinitionsProcessor
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionNormalizer
     */
    private $definitionNormalizer;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionExploder
     */
    private $definitionExploder;

    public function __construct()
    {
        $this->definitionExploder = new \Vaimo\ComposerPatches\Patch\DefinitionExploder();
        $this->definitionNormalizer = new \Vaimo\ComposerPatches\Patch\DefinitionNormalizer();
    }

    public function normalizeDefinitions(array $patches)
    {
        $patchesPerPackage = array();

        foreach ($patches as $target => $packagePatches) {
            $normalizedPatches = array();
            
            foreach ($packagePatches as $patchLabel => $patchConfig) {
                $definitionItems = $this->definitionExploder->process($patchLabel, $patchConfig);
                
                foreach ($definitionItems as $patchItem) {
                    list($label, $data) = $patchItem;
                    
                    $normalizedPatches[] = $this->definitionNormalizer->process($target, $label, $data);    
                }
            }

            if (!$validPatches = array_filter($normalizedPatches)) {
                continue;
            }

            $patchesPerPackage[$target] = $validPatches;
        }

        return $patchesPerPackage;
    }
}
