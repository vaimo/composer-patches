<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

interface PatchesResetsResolverInterface
{
    /**
     * @param PackageRepository $repository
     * @param array $patches
     * @param array $filters
     * @return array
     */
    public function resolvePatchesResets(PackageRepository $repository, array $patches, array $filters);
}
