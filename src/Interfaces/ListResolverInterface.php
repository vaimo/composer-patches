<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface ListResolverInterface
{
    /**
     * @param array $patches
     * @return array
     */
    public function resolvePatchesQueue(array $patches);

    /**
     * @param array $patches
     * @param array $subset
     * @return array
     */
    public function resolveRelevantPatches(array $patches, array $subset);
}
