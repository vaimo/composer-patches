<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

use Vaimo\ComposerPatches\Exceptions\PackageResolverException;
use Vaimo\ComposerPatches\Composer\ConfigKeys;
use Composer\Repository\RepositoryInterface;

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
            $data[ConfigKeys::PACKAGES],
            $data[ConfigKeys::PACKAGES_DEV],
            $data[ConfigKeys::PLATFORM],
            $data[ConfigKeys::PLATFORM_DEV],
            $data[ConfigKeys::ALIASES],
            $data[ConfigKeys::MINIMUM_STABILITY],
            $data[ConfigKeys::STABILITY_FLAGS],
            $data[ConfigKeys::PREFER_STABLE],
            $data[ConfigKeys::PREFER_LOWEST],
            $data[ConfigKeys::PLATFORM_OVERRIDES]
        );
    }

    public function extractLockData(\Composer\Package\Locker $locker)
    {
        try {
            $lockData = $locker->getLockData();
        } catch (\LogicException $exception) {
            return array();
        }

        $repository = $locker->getLockedRepository(true);

        $aliases = array();

        foreach ($lockData[ConfigKeys::ALIASES] as $alias) {
            $package = $alias[ConfigKeys::ALIAS_PACKAGE];

            if (!isset($aliases[$package])) {
                $aliases[$package] = array();
            }

            $aliases[$package][$alias[ConfigKeys::ALIAS_VERSION]] = $alias;
        }

        return array_replace($lockData, array(
            ConfigKeys::PACKAGES => $this->packageDataToInstances(
                $lockData[ConfigKeys::PACKAGES],
                $repository
            ),
            ConfigKeys::PACKAGES_DEV => $this->packageDataToInstances(
                $lockData[ConfigKeys::PACKAGES_DEV],
                $repository
            ),
            ConfigKeys::ALIASES => $aliases,
            ConfigKeys::PLATFORM_OVERRIDES => isset($lockData[ConfigKeys::PLATFORM_OVERRIDES])
                ? $lockData[ConfigKeys::PLATFORM_OVERRIDES]
                : array()
        ));
    }

    private function packageDataToInstances(array $packages, RepositoryInterface $repository)
    {
        $matches = array();
        
        foreach ($packages as $package) {
            $match = $repository->findPackage($package['name'], '*');
            
            if ($match === null) {
                throw new PackageResolverException(
                    sprintf('Failed to acquire package object for: %s', $package['name'])
                );
            }
            
            $matches[] = $match;
        } 
        
        return $matches;
    }
}
