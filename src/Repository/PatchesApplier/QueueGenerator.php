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
        $patches = $this->listResolver->resolveRelevantPatches($patches, $patchesQueue);
        
        $queueTargets = $this->patchListUtils->getAllTargets($patchesQueue);
        
        $relatedQueue = $this->patchListUtils->getRelatedPatches($patches, $queueTargets);
        $relatedQueueTargets = $this->patchListUtils->getAllTargets($relatedQueue);

        $hardResetStubs = array_diff_key($patchesQueue, array_filter($patchesQueue));
        
        $patchesQueue = $this->patchListUtils->filterListByTargets(
            array_replace($patches, $patchesQueue),
            array_merge($relatedQueueTargets, $queueTargets)
        );

        $resetQueue = $this->repositoryStateAnalyser->collectPackageResets(
            $repositoryState, 
            $patchesQueue
        );

        $hardResetItems = array_diff(
            $resetQueue,
            array_keys(
                array_filter($this->patchListUtils->groupItemsByTarget($patches))
            )
        );

        $hardResetStubs = array_replace($hardResetStubs, array_fill_keys($hardResetItems, array()));
        
        $patchesQueue = $this->patchListUtils->filterListByTargets($patchesQueue, $resetQueue);
        $queueTargets = $this->patchListUtils->getAllTargets($patchesQueue);
        
        $otherItems = array_intersect_key($patches, array_flip($queueTargets));
        
        return array_replace($hardResetStubs, $otherItems, $patchesQueue);
    }
    
    public function generateRemovalQueue(array $patches, array $repositoryState)
    {
        $removals = array_filter(
            $this->repositoryStateAnalyser->collectPatchRemovals($repositoryState, $patches)
        );
        
        return $this->patchListUtils->embedInfoToItems(
            $removals,
            array(
                Patch::STATE_LABEL => '<fg=red>REMOVED</>',
                Patch::STATUS_MATCH => true
            )
        );
    }
}
