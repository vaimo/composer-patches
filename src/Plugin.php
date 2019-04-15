<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Plugin implements
    \Composer\Plugin\PluginInterface,
    \Composer\EventDispatcher\EventSubscriberInterface,
    \Composer\Plugin\Capable
{
    /**
     * @var \Vaimo\ComposerPatches\Package\OperationAnalyser
     */
    private $operationAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Strategies\BootstrapStrategy
     */
    private $bootstrapStrategy;

    /**
     * @var \Vaimo\ComposerPatches\Factories\BootstrapFactory
     */
    private $bootstrapFactory;

    /**
     * @var \Vaimo\ComposerPatches\Bootstrap
     */
    private $bootstrap;

    /**
     * @var \Vaimo\ComposerPatches\Repository\Lock\Sanitizer
     */
    private $lockSanitizer;
    
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $appIO)
    {
        $this->operationAnalyser = new \Vaimo\ComposerPatches\Package\OperationAnalyser();
        $this->bootstrapStrategy = new \Vaimo\ComposerPatches\Strategies\BootstrapStrategy();
        
        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer);

        $this->bootstrapFactory = new \Vaimo\ComposerPatches\Factories\BootstrapFactory($composer, $appIO);
        $this->lockSanitizer = new \Vaimo\ComposerPatches\Repository\Lock\Sanitizer($appIO);

        $this->bootstrap = $this->bootstrapFactory->create($configFactory);

        $pluginBootstrap = new \Vaimo\ComposerPatches\Composer\Plugin\Bootstrap($composer);

        $pluginBootstrap->preloadPluginClasses();
    }

    public function getCapabilities()
    {
        return array(
            'Composer\Plugin\Capability\CommandProvider' => '\Vaimo\ComposerPatches\Composer\CommandsProvider',
        );
    }

    public static function getSubscribedEvents()
    {
        return array(
            \Composer\Script\ScriptEvents::PRE_AUTOLOAD_DUMP => 'postInstall',
            \Composer\Installer\PackageEvents::PRE_PACKAGE_UNINSTALL => 'resetPackages'
        );
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        if (!$this->bootstrap) {
            return;
        }

        $repository = $event->getComposer()->getRepositoryManager()->getLocalRepository();

        if (!$this->bootstrapStrategy->shouldAllow()) {
            $repository->write();
        } else {
            $this->bootstrap->applyPatches($event->isDevMode());
        }

        $this->lockSanitizer->sanitize();
    }

    public function resetPackages(\Composer\Installer\PackageEvent $event)
    {
        if (!$this->operationAnalyser->isPatcherUninstallOperation($event->getOperation())) {
            return;
        }

        if (!getenv(\Vaimo\ComposerPatches\Environment::SKIP_CLEANUP)) {
            $this->bootstrap->stripPatches();
        }

        $this->bootstrap = null;
    }
}
