<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PackageConfigExtractorInterface
{
    /**
     * @param \Composer\Package\PackageInterface $package
     * @return array
     */
    public function getConfig(\Composer\Package\PackageInterface $package);
}
