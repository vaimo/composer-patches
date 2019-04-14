<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Interfaces\ListResolverInterface as ListResolver;
use Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;
use Vaimo\ComposerPatches\Strategies\OutputStrategy;
use Vaimo\ComposerPatches\Patch\Definition as Patch;

class BootstrapFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function create(
        ListResolver $listResolver = null,
        OutputStrategy $outputStrategy = null,
        array $config = array()
    ) {
        if ($listResolver === null) {
            $listResolver = new ListResolvers\ChangesListResolver(
                new ListResolvers\DirectListResolver()
            );
        }
        
        if ($outputStrategy === null) {
            $outputStrategy = new OutputStrategy(
                array(Patch::STATUS_NEW, Patch::STATUS_CHANGED, Patch::STATUS_MATCH)
            );
        }

        return new \Vaimo\ComposerPatches\Bootstrap(
            $this->composer,
            $this->io,
            $listResolver,
            $outputStrategy,
            $config
        );
    }
}
