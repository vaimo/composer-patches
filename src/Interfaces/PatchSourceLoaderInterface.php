<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchSourceLoaderInterface
{
    /**
     * @param \Composer\Package\PackageInterface $package
     * @param string $source
     * @return array
     */
    public function load(\Composer\Package\PackageInterface $package, $source);
}
