<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class DownloaderComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Composer\Package\RootPackageInterface
     */
    private $rootPackage;
    
    /**
     * @var \Composer\Util\RemoteFilesystem
     */
    private $remoteFilesystem;
    
    /**
     * @var bool
     */
    private $gracefulMode;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PathUtils
     */
    private $pathUtils;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Composer\Package\RootPackageInterface $rootPackage
     * @param \Composer\Util\RemoteFilesystem $remoteFilesystem
     * @param bool $gracefulMode
     */
    public function __construct(
        \Composer\Package\RootPackageInterface $rootPackage,
        \Composer\Util\RemoteFilesystem $remoteFilesystem,
        $gracefulMode = false
    ) {
        $this->rootPackage = $rootPackage;
        $this->remoteFilesystem = $remoteFilesystem;
        $this->gracefulMode = $gracefulMode;
        
        $this->pathUtils = new \Vaimo\ComposerPatches\Utils\PathUtils();
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if (!$patchData[PatchDefinition::URL]) {
                    continue;
                }

                $source = $patchData[PatchDefinition::URL];
                
                $filename = sprintf(
                    $this->pathUtils->composePath('%s', 'composer-patches-%s.patch'),
                    sys_get_temp_dir(),
                    md5($this->rootPackage->getName() . '|' . $source)
                );
                
                if (!file_exists($filename)) {
                    $hostname = parse_url($source, PHP_URL_HOST);
                    
                    try {
                        $this->remoteFilesystem->copy($hostname, $source, $filename, false);
                    } catch (\Composer\Downloader\TransportException $exception) {
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

                            $patchData[PatchDefinition::STATUS_LABEL] = 'UNSECURE';
                        }
                        
                        if ($exception->getCode() === 404) {
                            $patchData[PatchDefinition::STATUS_LABEL] = 'ERROR 404';
                        }
                        
                        if ($this->gracefulMode) {
                            continue;
                        }

                        throw $exception;
                    }
                }

                $patchData[PatchDefinition::PATH] = $filename;
                $patchData[PatchDefinition::TMP] = true;
            }
        }

        return $patches;
    }
}
