<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

use Vaimo\ComposerPatches\Config as PluginConfig;

class PatchesFile implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;
    
    /**
     * @var \Vaimo\ComposerPatches\Package\ConfigReader
     */
    private $configLoader;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager
    ) {
        $this->installationManager = $installationManager;
        
        $this->configLoader = new \Vaimo\ComposerPatches\Package\ConfigReader();
    }

    public function load(\Composer\Package\PackageInterface $package, $source)
    {
        if (!is_array($source)) {
            $source = array($source);
        }

        $basePath = $this->installationManager->getInstallPath($package);
        
        $groups = array();
        
        foreach ($source as $item) {
            $fileContents = $this->configLoader->readToArray($basePath . DIRECTORY_SEPARATOR . $item);
            
            if (isset($fileContents[PluginConfig::DEFINITIONS_LIST])) {
                $fileContents = $fileContents[PluginConfig::DEFINITIONS_LIST];
            }

            $groups[] = $fileContents;
        }

        return $groups;
    }
}
