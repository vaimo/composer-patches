<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Strategies\Package;

class DefaultResetStrategy implements \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var \Composer\Downloader\DownloadManager
     */
    private $downloadManager;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Composer\Downloader\DownloadManager $downloadManager
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        \Composer\Downloader\DownloadManager $downloadManager
    ) {
        $this->installationManager = $installationManager;
        $this->downloadManager = $downloadManager;
    }

    public function shouldAllowReset(\Composer\Package\PackageInterface $package)
    {
        $downloader = $this->downloadManager->getDownloaderForInstalledPackage($package);

        if ($downloader instanceof \Composer\Downloader\ChangeReportInterface) {
            $installPath = $this->installationManager->getInstallPath($package);

            return !(bool)$downloader->getLocalChanges($package , $installPath);
        }

        return true;
    }
}
