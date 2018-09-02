<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;
use Vaimo\ComposerPatches\Interfaces\PatchesResetsResolverInterface;

class QueueGenerator
{
    /**
     * @var \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver
     */
    private $listResolver;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PatchesResetsResolverInterface[]
     */
    private $resetResolvers;

    /**
     * @var array
     */
    private $filters;

    /**
     * @param \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver $listResolver,
     * @param PatchesResetsResolverInterface[] $resetResolvers
     * @param array $filters
     */
    public function __construct(
        \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver $listResolver,
        array $resetResolvers,
        array $filters
    ) {
        $this->listResolver = $listResolver;
        $this->resetResolvers = $resetResolvers;
        $this->filters = $filters;
    }

    public function generate(PackageRepository $repository, array $patches)
    {
        $patchesQueue = $this->listResolver->resolvePatchesQueue(
            $patches, 
            $this->filters
        );

        $resetsQueue = array();

        foreach ($this->resetResolvers as $resolver) {
            $resetsQueue = array_unique(
                array_merge(
                    $resetsQueue, 
                    $resolver->resolvePatchesResets($repository, $patchesQueue, $this->filters)
                )
            );
        }
        
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
