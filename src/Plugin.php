<?php
namespace Vaimo\ComposerPatches;

class Plugin implements \Composer\Plugin\PluginInterface, \Composer\EventDispatcher\EventSubscriberInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Composer\Utils
     */
    protected $composerUtils;

    /**
     * @var \Vaimo\ComposerPatches\Patches
     */
    protected $patchesManager;

    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $this->patchesManager = new \Vaimo\ComposerPatches\Patches($composer, $io);
        $this->composerUtils = new \Vaimo\ComposerPatches\Composer\Utils();
    }

    public static function getSubscribedEvents()
    {
        return array(
            \Composer\Installer\PackageEvents::PRE_PACKAGE_INSTALL => 'resetAppliedPatches',
            \Composer\Installer\PackageEvents::PRE_PACKAGE_UPDATE => 'resetAppliedPatches',
            \Composer\Script\ScriptEvents::PRE_AUTOLOAD_DUMP => 'postInstall'
        );
    }

    public function resetAppliedPatches(\Composer\Installer\PackageEvent $event)
    {
        foreach ($event->getOperations() as $operation) {
            if ($operation->getJobType() != 'install') {
                continue;
            }

            $this->patchesManager->resetAppliedPatchesInfoForPackage(
                $this->composerUtils->getPackageFromOperation($operation)
            );
        }
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        $this->patchesManager->applyPatches();
    }
}
