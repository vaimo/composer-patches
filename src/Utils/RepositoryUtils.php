<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Composer\Repository\WritableRepositoryInterface;

class RepositoryUtils
{
    /**
     * @var \Vaimo\ComposerPatches\Compatibility\DependenciesFactory
     */
    private $composerDependencies;

    public function __construct()
    {
        $this->composerDependencies = new \Vaimo\ComposerPatches\Compatibility\DependenciesFactory();
    }

    public function filterByDependency(WritableRepositoryInterface $repository, $dependencyName)
    {
        $depsRepository = $this->composerDependencies->createCompositeRepository($repository);
        $dependents = $depsRepository->getDependents($dependencyName);
        $dependentsList = array_column($dependents, 0);

        return array_filter($dependentsList);
    }
}
