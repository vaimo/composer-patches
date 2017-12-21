<?php
namespace Vaimo\ComposerPatches;

class Plugin implements \Composer\Plugin\PluginInterface,
    \Composer\EventDispatcher\EventSubscriberInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Bootstrap
     */
    private $bootstrap;
    
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io) 
    {
        $this->bootstrap = new \Vaimo\ComposerPatches\Bootstrap($composer, $io);
    }

    public static function getSubscribedEvents()
    {
        return array(
            \Composer\Script\ScriptEvents::PRE_UPDATE_CMD => 'extractPatchesFromLock',
            \Composer\Script\ScriptEvents::PRE_INSTALL_CMD => 'extractPatchesFromLock',
            \Composer\Script\ScriptEvents::PRE_AUTOLOAD_DUMP => 'postInstall',
            \Composer\Installer\PackageEvents::PRE_PACKAGE_UNINSTALL => 'uninstall'
        );
    }

    public function extractPatchesFromLock()
    {
        $this->bootstrap->prepare();
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        $this->bootstrap->apply(
            $event->isDevMode()
        );
    }
    
    public function uninstall(\Composer\Installer\PackageEvent $event)
    {
        /** @var \Composer\DependencyResolver\Operation\UninstallOperation $operation */
        $operation = $event->getOperation();
        
        $extra = $operation->getPackage()->getExtra();
        
        if (empty($extra[\Vaimo\ComposerPatches\Config::PATCHER_PLUGIN_MARKER])) {
            return;
        }
        
        if (getenv(\Vaimo\ComposerPatches\Environment::NO_CLEANUP)) {
            return;
        }
        
        $this->bootstrap->unload(
            $event->isDevMode()
        );
    }
}
