<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier;

class QueueGenerator
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $listResolver;

    /**
     * @var \Vaimo\ComposerPatches\Repository\Analyser
     */
    private $itemsAnalyser;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;
    
    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver,
     * @param \Vaimo\ComposerPatches\Repository\Analyser $itemsAnalyser
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver,
        \Vaimo\ComposerPatches\Repository\Analyser $itemsAnalyser
    ) {
        $this->listResolver = $listResolver;
        $this->itemsAnalyser = $itemsAnalyser;

        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function generate(array $patches, array $repositoryState)
    {
        $patchesQueue = $this->listResolver->resolvePatchesQueue($patches);
        $patches = $this->listResolver->resolveRelevantPatches($patches, $patchesQueue);
        
        $queueTargets = $this->patchListUtils->getAllTargets($patchesQueue);
        
        $relatedQueue = $this->patchListUtils->getRelatedPatches($patches, $queueTargets);
        $relatedQueueTargets = $this->patchListUtils->getAllTargets($relatedQueue);

        $hardResetStubs = array_diff_key(
            $patchesQueue, 
            array_filter($patchesQueue)
        );
        
        $patchesQueue = $this->patchListUtils->filterListByTargets(
            array_replace($patches, $patchesQueue),
            array_merge($relatedQueueTargets, $queueTargets)
        );

        $resetQueue = $this->itemsAnalyser->determinePackageResets(
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
        $otherItems = array_intersect_key($patches, array_flip($this->patchListUtils->getAllTargets($patchesQueue)));
        
        return array_replace($hardResetStubs, $otherItems, $patchesQueue);
    }
}
