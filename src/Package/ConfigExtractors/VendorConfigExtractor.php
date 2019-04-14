<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package\ConfigExtractors;

use Vaimo\ComposerPatches\Config;

class VendorConfigExtractor implements \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $packageInfoResolver;
    
    /**
     * @var \Vaimo\ComposerPatches\Package\ConfigReader
     */
    private $configLoader;

    /**
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
    ) {
        $this->packageInfoResolver = $packageInfoResolver;
        
        $this->configLoader = new \Vaimo\ComposerPatches\Package\ConfigReader();
    }

    public function getConfig(\Composer\Package\PackageInterface $package, $configKey)
    {
        $source = $this->packageInfoResolver->getSourcePath($package)
            . DIRECTORY_SEPARATOR
            . Config::PACKAGE_CONFIG_FILE;
        
        if (!file_exists($source)) {
            return null;
        }

        $fileContents = $this->configLoader->readToArray($source);

        if (!isset($fileContents[$configKey])) {
            return null;
        }

        return $fileContents[$configKey];
    }
}
