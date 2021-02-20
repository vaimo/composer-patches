<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class ChangesListResolver implements \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $baseResolver;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer
     */
    private $patchListTransformer;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
    ) {
        $this->baseResolver = $baseResolver;

        $this->patchListTransformer = new \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer();
    }

    public function resolvePatchesQueue(array $patches)
    {
        return $this->baseResolver->resolvePatchesQueue($patches);
    }

    public function resolveRelevantPatches(array $patches, array $subset)
    {
        return $this->baseResolver->resolveRelevantPatches($patches, $subset);
    }

    public function resolveInitialState(array $patches, array $state)
    {
        $patchesByTarget = $this->patchListTransformer->groupItemsByTarget($patches);
        $unpackedState = $this->patchListTransformer->createDetailedList($state);

        $matches = array();

        foreach ($patchesByTarget as $target => $items) {
            foreach ($items as $path => $item) {
                if (!isset($unpackedState[$target][$path])) {
                    continue;
                }

                $stateItem = $unpackedState[$target][$path];

                if ($stateItem[Patch::HASH] === $item[Patch::HASH]) {
                    continue;
                }

                unset($unpackedState[$target][$path]);

                if (!isset($matches[$target])) {
                    $matches[$target] = array();
                }

                $matches[$target][$path] = $item;
            }
        }

        $state = $this->patchListTransformer->createSimplifiedList($unpackedState);

        return $this->baseResolver->resolveInitialState($matches, $state);
    }
}
