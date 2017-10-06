<?php
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

use Vaimo\ComposerPatches\Config as PluginConfig;

class PatchesFile implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Json\Decoder
     */
    private $jsonDecoder;

    public function __construct()
    {
        $this->jsonDecoder = new \Vaimo\ComposerPatches\Json\Decoder();
    }

    public function load($source)
    {
        $fileContents = $this->jsonDecoder->decode(
            file_get_contents($source)
        );

        if (isset($fileContents[PluginConfig::LIST])) {
            return $fileContents[PluginConfig::LIST];
        } elseif (!$fileContents) {
            throw new \Exception('There was an error in the supplied patch file');
        }

        return array();
    }
}
