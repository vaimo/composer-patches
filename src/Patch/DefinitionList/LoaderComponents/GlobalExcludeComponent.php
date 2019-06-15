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
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @param array $config
     */
    public function __construct(
        array $config
    ) {
        $this->config = $config;
        
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
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

        return $this->patchListUtils->applyDefinitionFilter(
            $patches,
            function ($patchData) use ($excludedPatches) {
                $owner = $patchData[PatchDefinition::OWNER];
                $source = $patchData[PatchDefinition::SOURCE];

                if (!isset($excludedPatches[$owner])) {
                    return true;
                }

                if (!preg_match($excludedPatches[$owner], $source)) {
                    return true;
                }
                
                return false;
            }
        );
    }
}
