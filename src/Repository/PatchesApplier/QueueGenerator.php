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
    private $repoStateAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Analyser
     */
    private $patchListAnalyser;
    
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Updater
     */
    private $patchListUpdater;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer
     */
    private $patchListTransformer;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;
    
    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver,
     * @param \Vaimo\ComposerPatches\Repository\State\Analyser $repoStateAnalyser
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver,
        \Vaimo\ComposerPatches\Repository\State\Analyser $repoStateAnalyser
    ) {
        $this->listResolver = $listResolver;
        $this->repoStateAnalyser = $repoStateAnalyser;

        $this->patchListAnalyser = new \Vaimo\ComposerPatches\Patch\DefinitionList\Analyser();
        $this->patchListUpdater = new \Vaimo\ComposerPatches\Patch\DefinitionList\Updater();
        $this->patchListTransformer = new \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer();
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
        $patchesByTarget = $this->patchListTransformer->createTargetsList(array_map('array_filter', $patches));
        $patchFootprints = $this->patchListTransformer->createSimplifiedList($patchesByTarget);

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

        $changedItems = $this->patchListTransformer->createDetailedList($changedItems);
        $staticItems = $this->patchListTransformer->createDetailedList($staticItems);
        
        foreach ($patchesByTarget as $target => $items) {
            foreach ($items as $path => $item) {
                $item = array_replace($item, array(
                    Patch::STATUS_NEW => !isset($staticItems[$target][$path]) && !isset($changedItems[$target][$path]),
                    Patch::STATUS_CHANGED => isset($changedItems[$target][$path])
                ));
                
                $patchesByTarget[$target][$path] = array_replace($item, array(
                    Patch::STATUS => $this->resolveStatusCode($item)
                ));
            }
        }
        
        return $this->patchListUtils->mergeLists(
            $patches,
            $this->patchListTransformer->createOriginList($patchesByTarget)
        );
    }
    
    private function resolveStatusCode(array $item)
    {
        $status = isset($item[Patch::STATUS])
            ? $item[Patch::STATUS]
            : '';

        if ($item[Patch::STATUS_NEW] && !$status) {
            $status = 'new';
        }

        if ($item[Patch::STATUS_CHANGED] && !$status) {
            $status = 'changed';
        }
        
        return $status;
    }
    
    private function resolveAffectedPatches($includes, $removals, $patches)
    {
        $queueTargets = $this->patchListAnalyser->getAllTargets(
            $this->patchListUtils->mergeLists($includes, $removals)
        );

        $affectedPatches = $this->patchListAnalyser->getRelatedPatches($patches, $queueTargets);

        $patchesByTarget = $this->patchListTransformer->createTargetsList($affectedPatches);
        
        return $this->patchListTransformer->createOriginList(
            $this->patchListUtils->diffListsByName($patchesByTarget, $removals)
        );
    }
    
    private function resolveChangesInState($patches, $patchesQueue, $repositoryState)
    {
        $relevantPatches = $this->listResolver->resolveRelevantPatches($patches, $patchesQueue);

        $removals = $this->repoStateAnalyser->collectPatchRemovals($repositoryState, $relevantPatches);
        $includes = $this->repoStateAnalyser->collectPatchIncludes($repositoryState, $relevantPatches);
        
        return array(array_filter($includes), array_filter($removals));
    }
    
    private function buildChangeQueues($includes, $removals, $patchesQueue)
    {
        $patchesQueueByTarget = $this->patchListTransformer->createTargetsList($patchesQueue);
        
        $includesQueue = $this->patchListTransformer->createOriginList(
            $this->patchListUtils->intersectListsByName($patchesQueueByTarget, $includes)
        );

        $removalsQueue = $this->patchListUpdater->embedInfoToItems($removals, false);
        
        return array($includesQueue, $removalsQueue);
    }
    
    public function generateRemovalQueue(array $patches, array $repositoryState)
    {
        $state = $this->patchListTransformer->createDetailedList($repositoryState);

        $stateMatches = $this->patchListUtils->intersectListsByName($state, $patches);

        $state = $this->patchListTransformer->createSimplifiedList($stateMatches);
        
        $removals = $this->repoStateAnalyser->collectPatchRemovals(
            $state,
            array_map('array_filter', $patches)
        );
        
        return $this->patchListUpdater->embedInfoToItems(
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
        
        $declaredTargets = $this->patchListAnalyser->getAllTargets(
            array_map('array_filter', $patches)
        );
        
        return array_unique(
            array_merge($directTargets, $declaredTargets)
        );
    }
}
