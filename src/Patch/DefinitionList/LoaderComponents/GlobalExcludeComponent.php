<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class GlobalExcludeComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
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

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
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
