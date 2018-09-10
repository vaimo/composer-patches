<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Interfaces\ListResolverInterface;
use Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

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

    public function create(ListResolverInterface $listResolver = null, array $config = array())
    {
        if ($listResolver === null) {
            $listResolver = new ListResolvers\DirectListResolver();
        }

        return new \Vaimo\ComposerPatches\Bootstrap($this->composer, $this->io, $listResolver, $config);
    }    
}
