<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Strategies;

class BootstrapStrategy
{
    /**
     * @var \Vaimo\ComposerPatches\Composer\Context
     */
    private $composerContext;

    /**
     * @param \Vaimo\ComposerPatches\Composer\Context $composerContext
     */
    public function __construct(
        \Vaimo\ComposerPatches\Composer\Context $composerContext
    ) {
        $this->composerContext = $composerContext;
    }

    public function shouldAllow()
    {
        if (getenv('COMPOSER_PATCHER_ALLOW_GLOBAL_USAGE')) {
            return true;
        }

        if (!$this->isPluginAvailable()) {
            return false;
        }

        $lockUpdateArgument = 'lock';
        
        try {
            $input = new \Symfony\Component\Console\Input\ArgvInput();

            return !$input->hasParameterOption(sprintf('--%s', $lockUpdateArgument));
        } catch (\Exception $e) {
            // There are situations where composer is accessed from non-CLI entry-points,
            // which will cause $argv not to be available, resulting a crash.
        }

        return false;
    }
    
    private function isPluginAvailable()
    {
        $composer = $this->composerContext->getLocalComposer();

        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($composer->getPackage())
        );

        $repository = $composer->getRepositoryManager()->getLocalRepository();

        try {
            $packageResolver->resolveForNamespace($repository->getCanonicalPackages(), __NAMESPACE__);
        } catch (\Vaimo\ComposerPatches\Exceptions\PackageResolverException $exception) {
            return false;
        }
        
        return true;
    }
}
