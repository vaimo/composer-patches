<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class InvertedListResolver implements \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $baseResolver;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
    ) {
        $this->baseResolver = $baseResolver;

        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function resolvePatchesQueue(array $patches)
    {
        $exclusions = $this->baseResolver->resolvePatchesQueue($patches);
        $targets = $this->patchListUtils->getAllTargets($exclusions);

        $matches = array();

        foreach ($exclusions as $target => $items) {
            $matches[$target] = array_diff_key($patches[$target], $items);
        }
        
        foreach ($targets as $target) {
            if (isset($matches[$target])) {
                continue;
            }
            
            $items = isset($patches[$target]) ? $patches[$target] : array();

            $matches[$target] = array_filter($items, function ($item) use ($targets) {
                return array_intersect_key($item[Patch::TARGETS], $targets);
            });
        }

        return array_replace(
            array_fill_keys($targets, array()),
            $this->patchListUtils->filterListByTargets($matches, $targets)
        );
    }
}
