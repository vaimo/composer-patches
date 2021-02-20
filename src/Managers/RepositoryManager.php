<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;
use Composer\Package\PackageInterface;

use Vaimo\ComposerPatches\Composer\ResetOperation;

class RepositoryManager
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface
     */
    private $packageResetStrategy;

    /**
     * @var \Vaimo\ComposerPatches\Console\Silencer
     */
    private $consoleSilencer;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface $packageResetStrategy
     * @param \Vaimo\ComposerPatches\Console\Silencer $consoleSilencer
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface $packageResetStrategy,
        \Vaimo\ComposerPatches\Console\Silencer $consoleSilencer
    ) {
        $this->installationManager = $installationManager;
        $this->packageResetStrategy = $packageResetStrategy;
        $this->consoleSilencer = $consoleSilencer;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param WritableRepositoryInterface $repository
     * @param PackageInterface $package
     * @throws \Vaimo\ComposerPatches\Exceptions\PackageResetException
     */
    public function resetPackage(WritableRepositoryInterface $repository, PackageInterface $package)
    {
        $operation = new ResetOperation($package, 'Package reset due to changes in patches configuration');

        if (!$this->packageResetStrategy->shouldAllowReset($package)) {
            throw new \Vaimo\ComposerPatches\Exceptions\PackageResetException(
                sprintf('Package reset halted due to encountering local changes: %s', $package->getName())
            );
        }

        $installer = $this->installationManager;

        $this->consoleSilencer->applyToCallback(
            function () use ($installer, $repository, $operation) {
                $installer->install($repository, $operation);
            }
        );
    }
}
