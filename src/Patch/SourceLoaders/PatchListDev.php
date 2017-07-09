<?php
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

class PatchListDev implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    public function load($source)
    {
        return $source;
    }
}
