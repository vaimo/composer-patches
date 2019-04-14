<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SorterComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;
    
    public function __construct()
    {
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        $sortKeys = array(PatchDefinition::BEFORE, PatchDefinition::AFTER);
        
        foreach ($patches as $patchTarget => $packagePatches) {
            foreach ($packagePatches as $patchPath => $patchInfo) {
                $otherPatches = array_diff(array_keys($packagePatches), array($patchPath));

                if (!$otherPatches) {
                    continue;
                }
                
                foreach ($sortKeys as $sortKey) {
                    if (!$patchInfo[$sortKey]) {
                        continue;
                    }

                    $filter = $this->filterUtils->composeRegex($patchInfo[$sortKey], '/');

                    $packagePatches[$patchPath][$sortKey] = preg_grep($filter, $otherPatches);
                }
            }
            
            $patchDependencies = array_fill_keys(array_keys($packagePatches), array());
            
            foreach ($packagePatches as $patchPath => $patchInfo) {
                $patchDependencies[$patchPath] = array_merge(
                    $patchDependencies[$patchPath],
                    $patchInfo[PatchDefinition::AFTER]
                );
                
                foreach ($patchInfo[PatchDefinition::BEFORE] as $beforePath) {
                    $patchDependencies[$beforePath][] = $patchPath;
                }
            }

            if (!array_filter($patchDependencies)) {
                continue;
            }

            $sorter = new \MJS\TopSort\Implementations\StringSort();

            foreach ($patchDependencies as $path => $depends) {
                $sorter->add($path, array_unique($depends));
            }
            
            $patches[$patchTarget] = array_replace(
                array_flip($sorter->sort()),
                $packagePatches
            );
        }

        return $patches;
    }
}
