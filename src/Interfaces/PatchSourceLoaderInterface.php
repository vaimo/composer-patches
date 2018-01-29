<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchSourceLoaderInterface
{
    /**
     * @param \Composer\Package\PackageInterface $package
     * @param $source
     * @return array
     */
    public function load(\Composer\Package\PackageInterface $package, $source);
}
