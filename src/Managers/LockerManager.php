<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

use Vaimo\ComposerPatches\Exceptions\PackageResolverException;

class LockerManager
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageListUtils
     */
    private $packageListUtils;

    public function __construct()
    {
        $this->packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();
    }

    public function updateLockData(\Composer\Package\Locker $locker, array $data)
    {
        $locker->setLockData(
            $data['packages'],
            $data['packages-dev'],
            $data['platform'],
            $data['platform-dev'],
            $data['aliases'],
            $data['minimum-stability'],
            $data['stability-flags'],
            $data['prefer-stable'],
            $data['prefer-lowest'],
            $data['platform-overrides']
        );
    }

    public function extractLockData(\Composer\Package\Locker $locker)
    {
        $lockData = $locker->getLockData();
        $repository = $locker->getLockedRepository(true);
        $packages = $this->packageListUtils->listToNameDictionary($repository->getPackages());

        $aliases = array();

        foreach ($lockData['aliases'] as $alias) {
            $package = $alias['package'];

            if (!isset($aliases[$package])) {
                $aliases[$package] = array();
            }

            $aliases[$package][$alias['version']] = $alias;
        }

        return array_replace($lockData, array(
            'packages' => $this->packageDataToInstances($lockData['packages'], $packages),
            'packages-dev' => $this->packageDataToInstances($lockData['packages-dev'], $packages),
            'aliases' => $aliases,
            'platform-overrides' => isset($lockData['platform-overrides'])
                ? $lockData['platform-overrides']
                : array()
        ));
    }

    private function packageDataToInstances(array $data, array $packages)
    {
        $targets = $this->packageListUtils->listToNameDictionary($data);

        $result = array_replace(
            $targets,
            array_intersect_key($packages, $targets)
        );

        if ($invalidTargets = array_filter($result, 'is_array')) {
            throw new PackageResolverException(
                sprintf('Failed to locate package object for: %s', key($invalidTargets))
            );
        }

        return array_values($result);
    }
}
