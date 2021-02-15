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
        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            $depsRepository = new \Composer\Repository\CompositeRepository(array($repository));
        } else {
            $depsRepository = new \Composer\Repository\InstalledRepository(array($repository));
        }

        return array_filter(
            array_map('reset', $$depsRepository->getDependents($dependencyName))
        );
    }
}
