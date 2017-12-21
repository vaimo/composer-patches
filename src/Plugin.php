<?php
namespace Vaimo\ComposerPatches;

class Plugin implements \Composer\Plugin\PluginInterface, \Composer\EventDispatcher\EventSubscriberInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Bootstrap
     */
    private $bootstrap;

    /**
     * @var \Vaimo\ComposerPatches\Package\OperationAnalyser
     */
    private $operationAnalyser;
    
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io) 
    {
        $this->bootstrap = new \Vaimo\ComposerPatches\Bootstrap($composer, $io);
        $this->operationAnalyser = new \Vaimo\ComposerPatches\Package\OperationAnalyser();
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

    public function extractPatchesFromLock()
    {
        if (!$this->bootstrap) {
            return;
        }
        
        $this->bootstrap->prepare();
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        if (!$this->bootstrap) {
            return;
        }
        
        $this->bootstrap->apply($event->isDevMode());
    }
    
    public function resetPackages(\Composer\Installer\PackageEvent $event)
    {
        if (!$this->operationAnalyser->isPatcherUninstallOperation($event->getOperation())) {
            return;
        }

        if (!getenv(\Vaimo\ComposerPatches\Environment::SKIP_CLEANUP)) {
            $this->bootstrap->unload();
        }
        
        $this->bootstrap = null;
    }
}
