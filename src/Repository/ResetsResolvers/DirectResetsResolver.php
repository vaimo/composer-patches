<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\ResetsResolvers;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

class DirectResetsResolver implements \Vaimo\ComposerPatches\Interfaces\PatchesResetsResolverInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Repository\Analyser
     */
    private $itemsAnalyser;

    /**
     * @param \Vaimo\ComposerPatches\Repository\Analyser $itemsAnalyser
     */
    public function __construct(
        \Vaimo\ComposerPatches\Repository\Analyser $itemsAnalyser
    ) {
        $this->itemsAnalyser = $itemsAnalyser;
    }
        
    public function resolvePatchesResets(PackageRepository $repository, array $patches, array $filters)
    {
        return array_keys(
            $this->itemsAnalyser->determinePackageResets($repository, $patches)
        );
    }
}
