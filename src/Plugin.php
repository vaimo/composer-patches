<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

use Composer\EventDispatcher\ScriptExecutionException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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

    /**
     * @var string[]
     */
    private $capabilitiesConfig = array();
    
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $appIO)
    {
        $this->operationAnalyser = new \Vaimo\ComposerPatches\Package\OperationAnalyser();
        $this->bootstrapStrategy = new \Vaimo\ComposerPatches\Strategies\BootstrapStrategy();
        
        $this->bootstrapFactory = new \Vaimo\ComposerPatches\Factories\BootstrapFactory($composer, $appIO);
        $this->lockSanitizer = new \Vaimo\ComposerPatches\Repository\Lock\Sanitizer($appIO);

        $this->bootstrap = $this->bootstrapFactory->create();

        $pluginBootstrap = new \Vaimo\ComposerPatches\Composer\Plugin\Bootstrap($composer);

        $pluginBootstrap->preloadPluginClasses();

        if (!interface_exists('\Composer\Plugin\Capability\CommandProvider')) {
            return;
        }
        
        $this->capabilitiesConfig = array(
            'Composer\Plugin\Capability\CommandProvider' => '\Vaimo\ComposerPatches\Composer\CommandsProvider',
        );
    }

    public function getCapabilities()
    {
        return $this->capabilitiesConfig;
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
        
        if (!$this->bootstrapStrategy->shouldAllow()) {
            return;
        }

        $bootstrap = $this->bootstrap;
        $lockSanitizer = $this->lockSanitizer;

        $runtimeUtils = new \Vaimo\ComposerPatches\Utils\RuntimeUtils();

        $result = $runtimeUtils->executeWithPostAction(
            function () use ($bootstrap, $event) {
                return $bootstrap->applyPatches($event->isDevMode());
            },
            function () use ($event, $lockSanitizer) {
                $repository = $event->getComposer()->getRepositoryManager()->getLocalRepository();

                $repository->write();
                $lockSanitizer->sanitize();
            }
        );
        
        if ($result) {
            return;
        }

        throw new ScriptExecutionException('Execution halted due to composer-patch failure', 1);
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
