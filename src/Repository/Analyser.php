<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class Analyser
{
    /**
     * @var \Vaimo\ComposerPatches\Package\Collector
     */
    private $packagesCollector;
    
    /**
     * @var \Vaimo\ComposerPatches\Repository\State\Analyser
     */
    private $repositoryStateAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
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
     * @param \Vaimo\ComposerPatches\Package\Collector $packagesCollector
     * @param \Vaimo\ComposerPatches\Repository\State\Analyser $repositoryStateAnalyser
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packagesCollector,
        \Vaimo\ComposerPatches\Repository\State\Analyser $repositoryStateAnalyser,
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver
    ) {
        $this->packagesCollector = $packagesCollector;
        $this->repositoryStateAnalyser = $repositoryStateAnalyser;
        $this->listResolver = $listResolver;

        $this->packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();
        $this->patchListUtils = new\Vaimo\ComposerPatches\Utils\PatchListUtils();
        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }
    
    public function getPatchesWithStatuses(PackageRepository $repository, array $patches, array $statusFilters)
    {
        $packages = $this->packagesCollector->collect($repository);
        
        $statusFilterRegex = $this->filterUtils->composeRegex($statusFilters, '/');

        $matches = $this->listResolver->resolvePatchesQueue($patches);

        $repositoryState = $this->packageListUtils->extractDataFromExtra(
            $packages,
            PluginConfig::APPLIED_FLAG,
            array()
        );

        $matchesFootprints = $this->patchListUtils->createSimplifiedList(
            $this->patchListUtils->groupItemsByTarget($matches)
        );

        $removedItems = $this->repositoryStateAnalyser->collectPatchRemovals($repositoryState, $patches);

        $matches = $this->patchListUtils->mergeLists($matches, $removedItems);

        $matches = array_replace(
            array_intersect_key($packages, $matches),
            $matches
        );

        $patchStatuses = array();

        foreach ($matchesFootprints as $target => $items) {
            $newItems = array_diff_key($items, $repositoryState[$target]);
            $changeItems = array_diff_key(array_diff_assoc($items, $repositoryState[$target]), $newItems);

            $patchStatuses = array_replace(
                $patchStatuses,
                array_fill_keys(array_keys($items), 'applied'),
                array_fill_keys(array_keys($newItems), 'new'),
                array_fill_keys(array_keys($changeItems), 'changed')
            );
        }

        foreach ($removedItems as $target => $items) {
            $patchStatuses = array_replace(
                $patchStatuses,
                array_fill_keys(array_keys($items), 'removed')
            );
        }

        $patchStatuses = preg_grep($statusFilterRegex, $patchStatuses);

        foreach ($matches as $target => $items) {
            foreach (array_keys($items) as $path) {
                if (isset($patchStatuses[$path])) {
                    $matches[$target][$path][PatchDefinition::STATUS] = $patchStatuses[$path];

                    continue;
                }

                unset($matches[$target][$path]);
            }
        }

        return $this->listResolver->resolvePatchesQueue(
            array_filter($matches)
        );
    }
}