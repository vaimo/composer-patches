<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchPackagesResolverInterface
{
    /**
     * @param array $patches
     * @param array $packages
     * @return bool[]
     */
    public function resolve(array $patches, array $packages);
}
