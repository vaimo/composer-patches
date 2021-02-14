<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Composer\Repository\WritableRepositoryInterface;

class RepositoryUtils
{
    public function filterByDependency(WritableRepositoryInterface $repository, $dependencyName)
    {
        $compositeRepository = new \Composer\Repository\CompositeRepository(array($repository));

        return array_filter(
            array_map('reset', $installedRepo->getDependents($dependencyName))
        );
    }
}
