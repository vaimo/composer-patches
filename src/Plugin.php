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

    /**
     * @var \Vaimo\ComposerPatches\Managers\AppliedPatchesManager
     */
    private $appliedPatchesManager;

    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $logger = new \Vaimo\ComposerPatches\Logger($io);

        $downloader = new \Composer\Util\RemoteFilesystem(
            $io,
            $composer->getConfig()
        );

        $this->appliedPatchesManager = new \Vaimo\ComposerPatches\Managers\AppliedPatchesManager();

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
            \Composer\Script\ScriptEvents::PRE_UPDATE_CMD => 'extractPatchesFromLock',
            \Composer\Script\ScriptEvents::PRE_INSTALL_CMD => 'extractPatchesFromLock',
            \Composer\Script\ScriptEvents::PRE_AUTOLOAD_DUMP => 'postInstall'
        );
    }

    public function extractPatchesFromLock(\Composer\Script\Event $event)
    {
        $this->appliedPatchesManager->extractAppliedPatchesInfo(
            $event->getComposer()->getRepositoryManager()->getLocalRepository()
        );
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        $composer = $event->getComposer();
        $repository = $composer->getRepositoryManager()->getLocalRepository();

        $this->appliedPatchesManager->restoreAppliedPatchesInfo($repository);

        $this->repositoryManager->processRepository(
            $repository,
            $composer->getConfig()->get('vendor-dir')
        );
    }
}
