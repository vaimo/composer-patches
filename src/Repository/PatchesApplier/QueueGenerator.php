<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class QueueGenerator
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $listResolver;

    /**
     * @var \Vaimo\ComposerPatches\Repository\State\Analyser
     */
    private $repositoryStateAnalyser;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;
    
    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver,
     * @param \Vaimo\ComposerPatches\Repository\State\Analyser $repositoryStateAnalyser
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver,
        \Vaimo\ComposerPatches\Repository\State\Analyser $repositoryStateAnalyser
    ) {
        $this->listResolver = $listResolver;
        $this->repositoryStateAnalyser = $repositoryStateAnalyser;
        
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function generateApplyQueue(array $patches, array $repositoryState)
    {
        $patchesQueue = $this->listResolver->resolvePatchesQueue($patches);

        $initialState = $this->listResolver->resolveInitialState($patchesQueue, $repositoryState);

        list($includes, $removals) = $this->resolveChangesInState($patches, $patchesQueue, $initialState);
        
        list($includesQueue, $removalsQueue) = $this->buildChangeQueues($includes, $removals, $patchesQueue);
        
        $affectedPatches = $this->resolveAffectedPatches($includes, $removals, $patches);

        $queue = array_reduce(
            array($affectedPatches, $includesQueue, $removalsQueue),
            array($this->patchListUtils, 'mergeLists'),
            array()
        );
        
        return $this->updateStatusMarkers($queue, $repositoryState);
    }
    
    private function updateStatusMarkers($patches, $repositoryState)
    {
        $patchesByTarget = $this->patchListUtils->createTargetsList(array_map('array_filter', $patches));
        $patchFootprints = $this->patchListUtils->createSimplifiedList($patchesByTarget);

        $staticItems = array();
        $changedItems = array();
        
        foreach ($patchFootprints as $target => $footprints) {
            if (!isset($repositoryState[$target])) {
                continue;
            }
            
            $staticItems[$target] = array_intersect_assoc($footprints, $repositoryState[$target]);
            $changedItems[$target] = array_diff_key(
                array_intersect_key($footprints, $repositoryState[$target]), 
                $staticItems[$target]
            );
        }

        $changedItems = $this->patchListUtils->createDetailedList($changedItems);
        $staticItems = $this->patchListUtils->createDetailedList($staticItems);
        
        foreach ($patchesByTarget as $target => $items) {
            foreach ($items as $path => $item) {
                $updates = array(
                    Patch::STATUS_NEW => !isset($staticItems[$target][$path]) && !isset($changedItems[$target][$path]),
                    Patch::STATUS_CHANGED => isset($changedItems[$target][$path])
                );

                $updates[Patch::STATUS] = isset($item[Patch::STATUS]) 
                    ? $item[Patch::STATUS] 
                    : '';

                if ($updates[Patch::STATUS_NEW] && !$updates[Patch::STATUS]) {
                    $updates[Patch::STATUS] = 'new';
                }
                
                if ($updates[Patch::STATUS_CHANGED] && !$updates[Patch::STATUS]) {
                    $updates[Patch::STATUS] = 'changed';
                }
                
                $patchesByTarget[$target][$path] = array_replace($item, $updates);
            }
        }
        
        return $this->patchListUtils->mergeLists(
            $patches,
            $this->patchListUtils->createOriginList($patchesByTarget)
        );
    }
    
    private function resolveAffectedPatches($includes, $removals, $patches)
    {
        $queueTargets = $this->patchListUtils->getAllTargets(
            $this->patchListUtils->mergeLists($includes, $removals)
        );

        $affectedPatches = $this->patchListUtils->getRelatedPatches($patches, $queueTargets);

        $patchesByTarget = $this->patchListUtils->createTargetsList($affectedPatches);
        
        return $this->patchListUtils->createOriginList(
            $this->patchListUtils->diffListsByName($patchesByTarget, $removals)
        );
    }
    
    private function resolveChangesInState($patches, $patchesQueue, $repositoryState)
    {
        $relevantPatches = $this->listResolver->resolveRelevantPatches($patches, $patchesQueue);

        $removals = $this->repositoryStateAnalyser->collectPatchRemovals($repositoryState, $relevantPatches);
        $includes = $this->repositoryStateAnalyser->collectPatchIncludes($repositoryState, $relevantPatches);
        
        return array(array_filter($includes), array_filter($removals));
    }
    
    private function buildChangeQueues($includes, $removals, $patchesQueue)
    {
        $patchesQueueByTarget = $this->patchListUtils->createTargetsList($patchesQueue);
        
        $includesQueue = $this->patchListUtils->createOriginList(
            $this->patchListUtils->intersectListsByName($patchesQueueByTarget, $includes)
        );

        $removalsQueue = $this->patchListUtils->embedInfoToItems($removals, false);
        
        return array($includesQueue, $removalsQueue);
    }
    
    public function generateRemovalQueue(array $patches, array $repositoryState)
    {
        $state = $this->patchListUtils->createDetailedList($repositoryState);

        $stateMatches = $this->patchListUtils->intersectListsByName($state, $patches);

        $state = $this->patchListUtils->createSimplifiedList($stateMatches);
        
        $removals = $this->repositoryStateAnalyser->collectPatchRemovals(
            $state,
            array_map('array_filter', $patches)
        );
        
        return $this->patchListUtils->embedInfoToItems(
            array_filter($removals),
            array(
                Patch::STATUS => 'removed',
                Patch::STATUS_MATCH => true
            )
        );
    }
    
    public function generateResetQueue(array $patches)
    {
        $directTargets = array_keys($patches);
        
        $declaredTargets = $this->patchListUtils->getAllTargets(
            array_map('array_filter', $patches)
        );
        
        return array_unique(
            array_merge($directTargets, $declaredTargets)
        );
    }
}
