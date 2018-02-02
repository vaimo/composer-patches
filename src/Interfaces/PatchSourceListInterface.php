<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchSourceListInterface
{
    public function getItems(\Composer\Repository\WritableRepositoryInterface $repository);
}
