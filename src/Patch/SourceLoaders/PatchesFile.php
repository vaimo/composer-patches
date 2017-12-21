<?php
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

use Vaimo\ComposerPatches\Config as PluginConfig;

class PatchesFile implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Package\ConfigReader
     */
    private $configLoader;

    public function __construct()
    {
        $this->configLoader = new \Vaimo\ComposerPatches\Package\ConfigReader();
    }

    public function load($source)
    {
        $fileContents = $this->configLoader->readToArray($source);
        
        if (isset($fileContents[PluginConfig::LIST])) {
            return $fileContents[PluginConfig::LIST];
        } elseif (!$fileContents) {
            throw new \Exception('There was an error in the supplied patch file');
        }

        return array();
    }
}
