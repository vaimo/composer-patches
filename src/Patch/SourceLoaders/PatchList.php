<?php
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

class PatchList implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    public function load(\Composer\Package\PackageInterface $package, $source)
    {
        return array($source);
    }
}
