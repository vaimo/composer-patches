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
     * @var \Vaimo\ComposerPatches\Composer\Context
     */
    private $composerContext;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $appIO;

    /**
     * @param \Vaimo\ComposerPatches\Composer\Context $composerContext
     * @param \Composer\IO\IOInterface $appIO
     */
    public function __construct(
        \Vaimo\ComposerPatches\Composer\Context $composerContext,
        \Composer\IO\IOInterface $appIO
    ) {
        $this->composerContext = $composerContext;
        $this->appIO = $appIO;
    }

    public function create(
        ConfigFactory $configFactory,
        ?ListResolver $listResolver = null,
        ?OutputStrategy $outputStrategy = null
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
            $this->composerContext,
            $this->appIO,
            $configFactory,
            $listResolver,
            $outputStrategy
        );
    }
}
