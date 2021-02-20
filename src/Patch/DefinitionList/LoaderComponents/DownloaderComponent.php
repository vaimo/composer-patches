<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Utils\PathUtils;

class DownloaderComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var bool
     */
    private $gracefulMode;

    /**
     * @var \Composer\Downloader\FileDownloader
     */
    private $fileDownloader;

    /**
     * @var \Vaimo\ComposerPatches\Console\Silencer
     */
    private $consoleSilencer;

    /**
     * @var \Composer\Package\PackageInterface
     */
    private $ownerPackage;

    /**
     * @var string
     */
    private $vendorDir;

    /**
     * @var \Vaimo\ComposerPatches\Compatibility\Executor
     */
    private $compExecutor;

    /**
     * @var \Vaimo\ComposerPatches\ErrorHandlers\TransportErrorHandler
     */
    private $errorHandler;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Composer\Package\PackageInterface $ownerPackage
     * @param \Composer\Downloader\FileDownloader $downloadManager
     * @param \Vaimo\ComposerPatches\Console\Silencer $consoleSilencer
     * @param string $vendorDir
     * @param bool $gracefulMode
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\Package\PackageInterface $ownerPackage,
        \Composer\Downloader\FileDownloader $downloadManager,
        \Vaimo\ComposerPatches\Console\Silencer $consoleSilencer,
        $vendorDir,
        $gracefulMode = false
    ) {
        $this->composer = $composer;
        $this->ownerPackage = $ownerPackage;
        $this->fileDownloader = $downloadManager;
        $this->consoleSilencer = $consoleSilencer;
        $this->vendorDir = $vendorDir;
        $this->gracefulMode = $gracefulMode;
        $this->compExecutor = new \Vaimo\ComposerPatches\Compatibility\Executor();
        $this->errorHandler = new \Vaimo\ComposerPatches\ErrorHandlers\TransportErrorHandler($gracefulMode);
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     * @throws \Exception
     */
    public function process(array $patches, array $packagesByName)
    {
        $ownerName = $this->ownerPackage->getName();
        $relativePath = PathUtils::composePath($ownerName, 'downloads');
        $absolutePath = PathUtils::composePath($this->vendorDir, $relativePath);
        $errors = array();
        $compExecutor = $this->compExecutor;
        $errorHandler = $this->errorHandler;

        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if (!$patchData[PatchDefinition::URL]) {
                    continue;
                }

                $source = $patchData[PatchDefinition::URL];
                $checksum = $patchData[PatchDefinition::CHECKSUM];
                $hashComponents = array($source, $checksum);
                $sourceHash = md5(implode('|', $hashComponents));
                $package = $this->createPackage($source, $sourceHash, $relativePath, $checksum);
                $destDir = PathUtils::composePath($absolutePath, $sourceHash);
                $destinationFile = PathUtils::composePath($destDir, basename($source));

                try {
                    $downloader = $this->fileDownloader;

                    $this->consoleSilencer->applyToCallback(
                        function () use (
                            $errorHandler,
                            $downloader,
                            $compExecutor,
                            $package,
                            $destDir,
                            $source,
                            &$patchData,
                            &$errors
                        ) {
                            $compExecutor->downloadPackage(
                                $downloader,
                                $package,
                                $source,
                                $destDir,
                                $errorHandler,
                                $patchData,
                                $errors
                            );
                        }
                    );
                } catch (\Composer\Downloader\TransportException $exception) {
                    $patchData[PatchDefinition::STATUS_LABEL] = $errorHandler->handleError($source, $exception);
                    $patchData[PatchDefinition::STATUS] = PatchDefinition::STATUS_ERRORS;
                }

                $compExecutor->assignTmpPathForPatchData($patchData, $destinationFile);
                $patchData[PatchDefinition::TMP] = true;
            }
        }

        $compExecutor->waitDownloadCompletion($this->composer);

        if (count($errors) > 0) {
            throw array_shift($errors);
        }

        return $patches;
    }

    private function createPackage($remoteFile, $name, $targetDir, $checksum = null)
    {
        $version = '0.0.0';
        $package = new \Composer\Package\Package($name, $version, $version);
        $package->setInstallationSource('dist');
        $package->setDistType('file');
        $package->setTargetDir($targetDir);
        $package->setDistUrl($remoteFile);

        if ($checksum) {
            $package->setDistSha1Checksum($checksum);
        }

        return $package;
    }
}
