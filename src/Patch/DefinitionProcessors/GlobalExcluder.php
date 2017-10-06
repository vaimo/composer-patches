<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class GlobalExcluder implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(
        array $config
    ) {
        $this->config = $config;
    }
    
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        $excludedPatches = array();
        
        if (isset($this->config[PluginConfig::EXCLUDED_PATCHES])) {
            foreach ($this->config[PluginConfig::EXCLUDED_PATCHES] as $patchOwner => $patchPaths) {
                if (!isset($excludedPatches[$patchOwner])) {
                    $excludedPatches[$patchOwner] = array();
                }

                $excludedPatches[$patchOwner] = array_flip($patchPaths);
            }
        }

        if (!$excludedPatches) {
            return $patches;
        }
        
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                $owner = $patchData[PatchDefinition::OWNER];
                $source = $patchData[PatchDefinition::SOURCE];
                
                if (isset($excludedPatches[$owner][$source])) {
                    $patchData = false;
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
