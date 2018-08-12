<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Composer\Repository\WritableRepositoryInterface;
use Vaimo\ComposerPatches\Composer\ConfigKeys as ComposerConfig;

class RepositoryUtils
{
    public function resolveForNamespace(WritableRepositoryInterface $repository, $namespace)
    {
        foreach ($repository->getCanonicalPackages() as $package) {
            if ($package->getType() !== ComposerConfig::COMPOSER_PLUGIN_TYPE) {
                continue;
            }

            $autoload = $package->getAutoload();

            if (!isset($autoload[ComposerConfig::PSR4_CONFIG])) {
                continue;
            }

            $matches = array_filter(
                array_keys($autoload[ComposerConfig::PSR4_CONFIG]),
                function ($item) use ($namespace) {
                    return strpos($namespace, rtrim($item, '\\')) === 0;
                }
            );

            if (!$matches) {
                continue;
            }

            return $package;
        }
        throw new \Exception('Failed to detect the plugin package');
    }

    public function filterByDependency(WritableRepositoryInterface $repository, $dependencyName)
    {
        $compositeRepository = new \Composer\Repository\CompositeRepository(array(
            $repository
        ));

        return array_filter(
            array_map('reset', $compositeRepository->getDependents($dependencyName))
        );
    }
}