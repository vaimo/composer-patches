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
    private $io;

    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @param \Composer\IO\ConsoleIO $io
     * @param \Composer\Installer\InstallationManager $installationManager
     */
    public function __construct(
        \Composer\IO\ConsoleIO $io,
        \Composer\Installer\InstallationManager $installationManager
    ) {
        $this->io = $io;
        $this->installationManager = $installationManager;
    }

    public function resetPackage(WritableRepositoryInterface $repository, PackageInterface $package)
    {
        $verbosityLevel = OutputUtils::resetVerbosity($this->io, OutputInterface::VERBOSITY_QUIET);

        $operation = new ResetOperation($package, 'Package reset due to changes in patches configuration');

        try {
            $this->installationManager->install($repository, $operation);
        } finally {
            OutputUtils::resetVerbosity($this->io, $verbosityLevel);
        }
    }
}

