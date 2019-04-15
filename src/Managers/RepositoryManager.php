<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;
use Composer\Package\PackageInterface;

use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\ComposerPatches\Composer\ResetOperation;
use Vaimo\ComposerPatches\Composer\OutputUtils;

class RepositoryManager
{
    /**
     * @var \Composer\IO\ConsoleIO
     */
    private $appIO;

    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installer;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface
     */
    private $packageResetStrategy;

    /**
     * @param \Composer\IO\ConsoleIO $appIO
     * @param \Composer\Installer\InstallationManager $installer
     * @param \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface $packageResetStrategy
     */
    public function __construct(
        \Composer\IO\ConsoleIO $appIO,
        \Composer\Installer\InstallationManager $installer,
        \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface $packageResetStrategy
    ) {
        $this->appIO = $appIO;
        $this->installer = $installer;
        $this->packageResetStrategy = $packageResetStrategy;
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
        $verbosityLevel = OutputUtils::resetVerbosity($this->appIO, OutputInterface::VERBOSITY_QUIET);

        $operation = new ResetOperation($package, 'Package reset due to changes in patches configuration');

        if (!$this->packageResetStrategy->shouldAllowReset($package)) {
            OutputUtils::resetVerbosity($this->appIO, $verbosityLevel);

            throw new \Vaimo\ComposerPatches\Exceptions\PackageResetException(
                sprintf('Package reset halted due to encountering local changes: %s', $package->getName())
            );
        }

        try {
            $this->installer->install($repository, $operation);
        } catch (\Exception $exception) {
            OutputUtils::resetVerbosity($this->appIO, $verbosityLevel);

            throw $exception;
        }

        OutputUtils::resetVerbosity($this->appIO, $verbosityLevel);
    }
}
