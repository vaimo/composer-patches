<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Plugin;

use Composer\Repository\WritableRepositoryInterface;

class PackageResolver
{
    /**
     * @var \Composer\Package\PackageInterface[]
     */
    private $additionalPackages;

    /**
     * @var \Vaimo\ComposerPatches\Package\ConfigAnalyser
     */
    private $packageAnalyser;

    /**
     * @param \Composer\Package\PackageInterface[] $additionalPackages
     */
    public function __construct(
        array $additionalPackages = array()
    ) {
        $this->additionalPackages = $additionalPackages;

        $this->packageAnalyser = new \Vaimo\ComposerPatches\Package\ConfigAnalyser();
    }

    /**
     * @param \Composer\Package\PackageInterface[] $packages
     * @param string $namespace
     * @return \Composer\Package\PackageInterface
     * @throws \Vaimo\ComposerPatches\Exceptions\PackageResolverException
     */
    public function resolveForNamespace(array $packages, $namespace)
    {
        $packages = array_merge($this->additionalPackages, $packages);

        foreach ($packages as $package) {
            if (!$this->packageAnalyser->isPluginPackage($package)) {
                continue;
            }

            if (!$this->packageAnalyser->ownsNamespace($package, $namespace)) {
                continue;
            }

            return $package;
        }
        
        throw new \Vaimo\ComposerPatches\Exceptions\PackageResolverException(
            'Failed to detect the plugin package'
        );
    }
}
