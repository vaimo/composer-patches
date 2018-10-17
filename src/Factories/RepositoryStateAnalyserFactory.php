<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Patch\PackageResolvers;
use Vaimo\ComposerPatches\Config as Config;

class RepositoryStateAnalyserFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @param \Composer\Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    public function create(Config $pluginConfig)
    {
        $resetsResolver = $pluginConfig->shouldResetEverything()
            ? new PackageResolvers\FullResetResolver()
            : new PackageResolvers\MissingPatchesResolver();
        
        return new \Vaimo\ComposerPatches\Repository\State\Analyser($resetsResolver);
    }
}
