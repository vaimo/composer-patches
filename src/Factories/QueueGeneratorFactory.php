<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Repository\ResetsResolvers;
use Vaimo\ComposerPatches\Patch\PackageResolvers;
use Vaimo\ComposerPatches\Config as Config;
use Vaimo\ComposerPatches\Repository\PatchesApplier;

class QueueGeneratorFactory
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

    public function create(Config $pluginConfig, \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver)
    {
        $resetsResolver = $pluginConfig->shouldResetEverything()
            ? new PackageResolvers\FullResetResolver()
            : new PackageResolvers\MissingPatchesResolver();
        
        $repositoryAnalyser = new \Vaimo\ComposerPatches\Repository\Analyser($resetsResolver);
        
        return new PatchesApplier\QueueGenerator($listResolver, $repositoryAnalyser);
    }
}
