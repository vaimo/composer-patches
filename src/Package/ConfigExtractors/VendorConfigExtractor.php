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
     * @var \Vaimo\ComposerPatches\Json\Decoder
     */
    private $jsonDecoder;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager
    ) {
        $this->installationManager = $installationManager;
        $this->jsonDecoder = new \Vaimo\ComposerPatches\Json\Decoder();
    }
    
    public function getConfig(\Composer\Package\PackageInterface $package)
    {
        $installPath = !$package instanceof \Composer\Package\RootPackage
            ? $this->installationManager->getInstallPath($package)
            : '.';

        $packageComposerFile = $installPath . '/composer.json';
        
        if (file_exists($packageComposerFile)) {
            $fileContents = $this->jsonDecoder->decode(
                file_get_contents($packageComposerFile)
            );

            if (isset($fileContents[Config::CONFIG_ROOT])) {
                return $fileContents[Config::CONFIG_ROOT];
            }
        }
        
        return array();
    }
}
