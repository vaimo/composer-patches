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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Composer\Package\PackageInterface $ownerPackage
     * @param \Composer\Downloader\FileDownloader $downloadManager
     * @param \Vaimo\ComposerPatches\Console\Silencer $consoleSilencer
     * @param string $vendorDir
     * @param bool $gracefulMode
     */
    public function __construct(
        \Composer\Package\PackageInterface $ownerPackage,
        \Composer\Downloader\FileDownloader $downloadManager,
        \Vaimo\ComposerPatches\Console\Silencer $consoleSilencer,
        $vendorDir,
        $gracefulMode = false
    ) {
        $this->ownerPackage = $ownerPackage;
        $this->fileDownloader = $downloadManager;
        $this->consoleSilencer = $consoleSilencer;
        $this->vendorDir = $vendorDir;
        $this->gracefulMode = $gracefulMode;
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
                
                $destinationFolder = PathUtils::composePath($absolutePath, $sourceHash);
                $destinationFile = PathUtils::composePath($destinationFolder, basename($source));
                
                try {
                    $downloader = $this->fileDownloader;

                    $this->consoleSilencer->applyToCallback(
                        function () use ($downloader, $package, $destinationFolder) {
                            $downloader->download($package, $destinationFolder, false);
                        }
                    );
                } catch (\Composer\Downloader\TransportException $exception) {
                    $patchData[PatchDefinition::STATUS_LABEL] = $this->handleTransportError($source, $exception);
                    $patchData[PatchDefinition::STATUS] = PatchDefinition::STATUS_ERRORS; 
                }

                $patchData[PatchDefinition::PATH] = $destinationFile;
                $patchData[PatchDefinition::TMP] = true;
            }
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
    
    private function handleTransportError($source, \Composer\Downloader\TransportException $exception)
    {
        $statusLabel = sprintf('ERROR %s', $exception->getCode());
        
        if (strpos($exception->getMessage(), 'configuration does not allow connections') !== false) {
            $exception = new \Composer\Downloader\TransportException(
                sprintf(
                    'Your configuration does not allow connections to %s. ' .
                    'Override the \'secure-http\' to allow: ' .
                    'https://github.com/vaimo/composer-patches#patcher-configuration',
                    $source
                ),
                $exception->getCode()
            );

            $statusLabel = 'UNSECURE';
        }

        if ($this->gracefulMode) {
            return $statusLabel;
        }

        throw $exception;
    }
}
