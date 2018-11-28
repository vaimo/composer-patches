<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Strategies\Package;

use Composer\Downloader\VcsCapableDownloaderInterface as VcsCapable;
use Composer\Downloader\ChangeReportInterface as ChangeReportCapable;

class DefaultResetStrategy implements \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installer;

    /**
     * @var \Composer\Downloader\DownloadManager
     */
    private $downloader;

    /**
     * @param \Composer\Installer\InstallationManager $installer
     * @param \Composer\Downloader\DownloadManager $downloader
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installer,
        \Composer\Downloader\DownloadManager $downloader
    ) {
        $this->installer = $installer;
        $this->downloader = $downloader;
    }

    public function shouldAllowReset(\Composer\Package\PackageInterface $package)
    {
        $downloader = $this->downloader->getDownloaderForInstalledPackage($package);

        if ($downloader instanceof ChangeReportCapable && $downloader instanceof VcsCapable) {
            $installPath = $this->installer->getInstallPath($package);

            return !(bool)$downloader->getLocalChanges($package, $installPath);
        }

        return true;
    }
}
