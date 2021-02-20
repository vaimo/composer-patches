<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\Package\PackageInterface;

class SourcesPreloader
{
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $infoResolver;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FileSystemUtils
     */
    private $fileSystemUtils;

    /**
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $infoResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $infoResolver
    ) {
        $this->infoResolver = $infoResolver;

        $this->fileSystemUtils = new \Vaimo\ComposerPatches\Utils\FileSystemUtils();
    }

    public function preload(PackageInterface $package)
    {
        $sourcePaths = $this->infoResolver->getAutoLoadPaths($package);

        $matchGroups = array();

        foreach ($sourcePaths as $sourcePath) {
            $matchGroups[] = $this->fileSystemUtils->collectPathsRecursively($sourcePath, '/.*\.php/');
        }

        $sourceFilePaths = array_unique(
            array_reduce($matchGroups, 'array_merge', array())
        );

        foreach ($sourceFilePaths as $filePath) {
            class_exists($filePath);
        }
    }
}
