<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

class QueueGenerator
{
    /**
     * @var \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver
     */
    private $listResolver;

    /**
     * @var array
     */
    private $filters;

    /**
     * @param \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver $listResolver,
     * @param array $filters
     */
    public function __construct(
        \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver $listResolver,
        array $filters
    ) {
        $this->listResolver = $listResolver;
        $this->filters = $filters;
    }

    public function generate(PackageRepository $repository, array $patches)
    {
        $patchesQueue = $this->listResolver->resolvePatchesQueue(
            $patches, 
            $this->filters
        );
        
        $resetsQueue = $this->listResolver->resolvePatchesResets(
            $repository, 
            $patchesQueue, 
            $this->filters
        );
        
        $relatedQueue = $this->listResolver->resolveRelatedQueue(
            $repository, 
            $patches, 
            $resetsQueue
        );
        
        foreach ($relatedQueue as $key => $items) {
            $patchesQueue[$key] = array_replace(
                isset($patchesQueue[$key]) ? $patchesQueue[$key] : array(),
                $items
            );
        }
        
        $resetQueue = array_merge(array_keys($relatedQueue), $resetsQueue);
        
        return array($patchesQueue, array_unique($resetQueue));
    }
}
