<?php
namespace Vaimo\ComposerPatches\Package\ConfigExtractors;

use Vaimo\ComposerPatches\Config;

class VendorConfigExtractor implements \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface
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
    
    public function getConfig(\Composer\Package\PackageInterface $package)
    {
        $installPath = !$package instanceof \Composer\Package\RootPackage
            ? $this->installationManager->getInstallPath($package)
            : '.';

        $source = $installPath . '/' . Config::PACKAGE_CONFIG_FILE;
        
        if (file_exists($source)) {
            $fileContents = $this->configLoader->readToArray($source);
            
            if (isset($fileContents[Config::CONFIG_ROOT])) {
                return $fileContents[Config::CONFIG_ROOT];
            }
        }
        
        return array();
    }
}
