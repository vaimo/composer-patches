<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Repository\ResetsResolvers;
use Vaimo\ComposerPatches\Patch\PackageResolvers;
use Vaimo\ComposerPatches\Config as Config;

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

    public function create(Config $pluginConfig, array $filters = array(), $resets = array())
    {
        $rootPackage = $this->composer->getPackage();

        $packageCollector = new \Vaimo\ComposerPatches\Package\Collector(array($rootPackage));

        $resolvers = array(
            'full' => new PackageResolvers\FullResetResolver(),
            'missing' => new PackageResolvers\MissingPatchesResolver()
        );
        
        $repositoryAnalyser = new \Vaimo\ComposerPatches\Repository\Analyser(
            $packageCollector,
            $resolvers[$pluginConfig->shouldResetEverything() ? 'full' : 'missing']
        );

        $missingItemsAnalyser = new \Vaimo\ComposerPatches\Repository\Analyser(
            $packageCollector,
            $resolvers['missing']
        );
        
        $resetsResolvers = array(
            'direct' => new ResetsResolvers\DirectResetsResolver($missingItemsAnalyser),
            'related' => new ResetsResolvers\RelatedResetsResolver($repositoryAnalyser)
        );

        $listResolver = new \Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolver(
            $repositoryAnalyser,
            $missingItemsAnalyser
        );
        
        return new \Vaimo\ComposerPatches\Repository\PatchesApplier\QueueGenerator(
            $listResolver,
            !$resets 
                ? $resetsResolvers 
                : array_intersect_key($resetsResolvers, array_flip($resets)),
            $filters
        );
    }
}
