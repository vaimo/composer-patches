<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class PatchListManager
{
    private $listResolver;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageListUtils 
     */
    private $packageListUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils 
     */
    private $patchListUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils 
     */
    private $filterUtils;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver
    ) {
        $this->listResolver = $listResolver;
        
        $this->packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();
        $this->patchListUtils = new\Vaimo\ComposerPatches\Utils\PatchListUtils();
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }

    public function getPatchesWithStatuses(array $patches, array $packages, array $statusFilters)
    {
        $statusFilterRegex = $this->filterUtils->composeRegex($statusFilters, '/');

        $matches = $this->listResolver->resolvePatchesQueue($patches);

        $repositoryState = $this->packageListUtils->extractExtraData(
            $packages,
            PluginConfig::APPLIED_FLAG
        );

        $matchesFootprints = $this->patchListUtils->createSimplifiedList(
            $this->patchListUtils->groupItemsByTarget($matches)
        );

        $patchesFootprints = $this->patchListUtils->createSimplifiedList(
            $this->patchListUtils->groupItemsByTarget($patches)
        );

        $patchStates = array();

        foreach ($matchesFootprints as $target => $items) {
            $newItems = array_diff_key($items, $repositoryState[$target]);
            $changeItems = array_diff_key(array_diff_assoc($items, $repositoryState[$target]), $newItems);

            $patchStates = array_replace(
                $patchStates,
                array_fill_keys(array_keys($items), 'applied'),
                array_fill_keys(array_keys($newItems), 'new'),
                array_fill_keys(array_keys($changeItems), 'changed')
            );
        }

        $removedItems = array();

        foreach ($repositoryState as $target => $items) {
            $removedItems[$target] = array_diff_key(
                array_diff_key(
                    $items,
                    isset($patchesFootprints[$target]) ? $patchesFootprints[$target] : array()
                ),
                $patchStates
            );
        }

        $removedItems = $this->patchListUtils->createDetailedList($removedItems);

        foreach ($removedItems as $target => $items) {
            $patchStates = array_replace(
                $patchStates,
                array_fill_keys(array_keys($items), 'removed')
            );
        }

        $matches = $this->patchListUtils->mergeLists($matches, $removedItems);

        $patchStates = preg_grep($statusFilterRegex, $patchStates);

        foreach ($matches as $target => $items) {
            foreach (array_keys($items) as $path) {
                if (isset($patchStates[$path])) {
                    $matches[$target][$path][PatchDefinition::STATE] = $patchStates[$path];

                    continue;
                }

                unset($matches[$target][$path]);
            }
        }

        return array_filter($matches);
    }    
}
