<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

class PatchList implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    public function load(\Composer\Package\PackageInterface $package, $source)
    {
        return array($source);
    }
}
