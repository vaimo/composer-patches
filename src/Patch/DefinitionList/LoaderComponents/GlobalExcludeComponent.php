<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Utils\FilterUtils;

class GlobalExcludeComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @param array $config
     */
    public function __construct(
        array $config
    ) {
        $this->config = $config;
        
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        $excludedPatches = array();
        
        foreach ($this->config as $patchOwner => $patchPaths) {
            if (!isset($excludedPatches[$patchOwner])) {
                $excludedPatches[$patchOwner] = array();
            }
            
            if (!$patchPaths) {
                continue;
            }
            
            $excludedPatches[$patchOwner] = $this->filterUtils->composeRegex($patchPaths, '/');
        }

        if (!$excludedPatches) {
            return $patches;
        }

        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                $owner = $patchData[PatchDefinition::OWNER];
                $source = $patchData[PatchDefinition::SOURCE];

                if (!isset($excludedPatches[$owner])) {
                    continue;
                }
                
                if (!preg_match($excludedPatches[$owner], $source)) {
                    continue;
                }

                $patchData = false;
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
