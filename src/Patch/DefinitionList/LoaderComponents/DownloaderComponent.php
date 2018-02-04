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
     * @var \Composer\Util\RemoteFilesystem
     */
    private $remoteFilesystem;

    /**
     * @param \Composer\Util\RemoteFilesystem $remoteFilesystem
     */
    public function __construct(
        \Composer\Util\RemoteFilesystem $remoteFilesystem
    ) {
        $this->remoteFilesystem = $remoteFilesystem;
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        foreach ($patches as $targetPackage => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if (!$patchData[PatchDefinition::URL]) {
                    continue;
                }

                $source = $patchData[PatchDefinition::URL];
                
                $filename = uniqid('/tmp/') . '.patch';
                $hostname = parse_url($source, PHP_URL_HOST);

                $this->remoteFilesystem->copy($hostname, $source, $filename, false);

                $patchData[PatchDefinition::PATH] = $filename;
            }
        }

        return $patches;
    }
}
