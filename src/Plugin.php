<?php
namespace Vaimo\ComposerPatches;

class Plugin implements \Composer\Plugin\PluginInterface,
    \Composer\EventDispatcher\EventSubscriberInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Composer\Utils
     */
    private $composerUtils;

    /**
     * @var \Vaimo\ComposerPatches\Managers\RepositoryManager
     */
    private $repositoryManager;

    /**
     * @var \Vaimo\ComposerPatches\Patch\PackageUtils
     */
    private $packageUtils;

    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $logger = new \Vaimo\ComposerPatches\Logger($io);

        $downloader = new \Composer\Util\RemoteFilesystem(
            $io,
            $composer->getConfig()
        );

        $patchesManager = new \Vaimo\ComposerPatches\Managers\PatchesManager(
            $composer->getEventDispatcher(),
            $downloader,
            $logger
        );

        $this->repositoryManager = new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $composer->getInstallationManager(),
            $composer->getPackage(),
            $logger,
            $patchesManager
        );

        $this->composerUtils = new \Vaimo\ComposerPatches\Composer\Utils();
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
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

            $this->packageUtils->resetAppliedPatches(
                $this->composerUtils->getPackageFromOperation($operation)
            );
        }
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        $composer = $event->getComposer();

        $this->repositoryManager->processRepository(
            $composer->getRepositoryManager()->getLocalRepository(),
            $composer->getConfig()->get('vendor-dir')
        );
    }
}
