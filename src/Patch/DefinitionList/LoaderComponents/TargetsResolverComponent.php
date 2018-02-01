<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Patch\Config as PatchConfig;

class TargetsResolverComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $packageInfoResolver;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Analyser
     */
    private $patchAnalyser;

    /**
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
    ) {
        $this->packageInfoResolver = $packageInfoResolver;

        $this->patchAnalyser = new \Vaimo\ComposerPatches\Patch\Analyser();
    }
    
    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        foreach ($patches as $patchTarget => $packagePatches) {
            foreach ($packagePatches as $patch => $info) {
                $targets = isset($info[PatchDefinition::TARGETS]) 
                    ? $info[PatchDefinition::TARGETS] 
                    : array();
                
                if (count($targets) > 1 || reset($targets) != PatchConfig::BUNDLE_TARGET) {
                    continue;
                }
                
                $path = $this->packageInfoResolver->getSourcePath($packagesByName[$patchTarget]) 
                    . DIRECTORY_SEPARATOR . $patch;
                
                if (!file_exists($path)) {
                    continue;
                }

                $paths = $this->patchAnalyser->getAllPaths(
                    file_get_contents($path)
                );
                
                if (!$targets = $this->packageInfoResolver->resolveNamesFromPaths($packagesByName, $paths)) {
                    continue;
                }

                $patches[$patchTarget][$patch][PatchDefinition::TARGETS] = $targets;
            }
        }
        
        return $patches;
    }
}
