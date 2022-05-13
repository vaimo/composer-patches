<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Compatibility;

use Composer\Downloader\FileDownloader;

class DependenciesFactory
{
    public function createCompositeRepository($repository)
    {
        if (version_compare(\Composer\Composer::getVersion(), '2.0', '<')) {
            return new \Composer\Repository\CompositeRepository(array($repository));
        }

        return  new \Composer\Repository\InstalledRepository(array($repository));
    }

    public function createFileDownloader($appIO, $composer, $composerConfig, $cache)
    {
        if (version_compare(\Composer\Composer::getVersion(), '2.0', '<')) {
            return new FileDownloader($appIO, $composerConfig, null, $cache);
        }

        $httpDownloader = $composer->getLoop()->getHttpDownloader();
        return new FileDownloader($appIO, $composerConfig, $httpDownloader, null, $cache);
    }

    public function createPackageDownloader($baseDownloader, $package)
    {
        if (version_compare(\Composer\Composer::getVersion(), '2.0', '<')) {
            return $baseDownloader->getDownloaderForInstalledPackage($package);
        }

        return $baseDownloader->getDownloaderForPackage($package);
    }
}
