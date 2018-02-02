<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Plugin implements \Composer\Plugin\PluginInterface, 
    \Composer\EventDispatcher\EventSubscriberInterface, \Composer\Plugin\Capable
{
    /**
     * @var \Vaimo\ComposerPatches\Package\OperationAnalyser
     */
    private $operationAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Managers\PatcherStateManager
     */
    private $patcherStateManager;

    /**
     * @var \Vaimo\ComposerPatches\Bootstrap
     */
    private $bootstrap;
    
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io) 
    {
        $this->operationAnalyser = new \Vaimo\ComposerPatches\Package\OperationAnalyser();
        $this->patcherStateManager = new \Vaimo\ComposerPatches\Managers\PatcherStateManager();
        
        $this->bootstrap = new \Vaimo\ComposerPatches\Bootstrap($composer, $io);
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
            \Composer\Script\ScriptEvents::PRE_UPDATE_CMD => 'extractPatchesFromLock',
            \Composer\Script\ScriptEvents::PRE_INSTALL_CMD => 'extractPatchesFromLock',
            \Composer\Script\ScriptEvents::PRE_AUTOLOAD_DUMP => 'postInstall',
            \Composer\Installer\PackageEvents::PRE_PACKAGE_UNINSTALL => 'resetPackages'
        );
    }

    public function extractPatchesFromLock(\Composer\Script\Event $event)
    {
        if (!$this->bootstrap) {
            return;
        }

        $this->patcherStateManager->extractAppliedPatchesInfo(
            $event->getComposer()->getRepositoryManager()->getLocalRepository()
        );
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        if (!$this->bootstrap) {
            return;
        }
        
        $this->patcherStateManager->restoreAppliedPatchesInfo(
            $event->getComposer()->getRepositoryManager()->getLocalRepository()
        );
        
        $this->bootstrap->applyPatches($event->isDevMode());
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
