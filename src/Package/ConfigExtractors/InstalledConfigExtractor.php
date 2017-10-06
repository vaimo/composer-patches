<?php
namespace Vaimo\ComposerPatches\Package\ConfigExtractors;

class InstalledConfigExtractor implements \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface
{
    public function getConfig(\Composer\Package\PackageInterface $package)
    {
        return $package->getExtra();
    }
}
