<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

use Composer\Composer;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;

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
     * @var \Vaimo\ComposerPatches\Compatibility\Executor
     */
    private $compExecutor;

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
        $this->compExecutor = new \Vaimo\ComposerPatches\Compatibility\Executor();
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param WritableRepositoryInterface $repository
     * @param PackageInterface $package
     * @throws \Vaimo\ComposerPatches\Exceptions\PackageResetException
     */
    public function resetPackage(
        WritableRepositoryInterface $repository,
        PackageInterface $package,
        Composer $composer
    ) {
        $resetOperation = new ResetOperation($package, 'Package reset due to changes in patches configuration');
        $uninstallOperation = new UninstallOperation($package);

        if (!$this->packageResetStrategy->shouldAllowReset($package)) {
            throw new \Vaimo\ComposerPatches\Exceptions\PackageResetException(
                sprintf('Package reset halted due to encountering local changes: %s', $package->getName())
            );
        }

        $compExecutor = $this->compExecutor;
        $installationManager = $this->installationManager;
        return $this->consoleSilencer->applyToCallback(
            function () use ($compExecutor, $installationManager, $repository, $resetOperation, $uninstallOperation, $composer) {
                return $compExecutor->processReinstallOperation($repository, $installationManager, $resetOperation, $uninstallOperation, $composer);
            }
        );
    }
}
