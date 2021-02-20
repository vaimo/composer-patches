<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

class InclusiveListResolver implements \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $baseResolver;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Analyser
     */
    private $patchListAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $baseResolver
    ) {
        $this->baseResolver = $baseResolver;

        $this->patchListAnalyser = new \Vaimo\ComposerPatches\Patch\DefinitionList\Analyser();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function resolvePatchesQueue(array $patches)
    {
        $matches = $this->baseResolver->resolvePatchesQueue($patches);
        $targets = $this->patchListAnalyser->getAllTargets($matches);

        return $this->patchListUtils->mergeLists(
            $this->patchListUtils->filterListByTargets($patches, $targets),
            $matches
        );
    }

    public function resolveRelevantPatches(array $patches, array $subset)
    {
        return $patches;
    }

    public function resolveInitialState(array $patches, array $state)
    {
        return $this->baseResolver->resolveInitialState($patches, $state);
    }
}
