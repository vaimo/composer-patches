<?php
namespace Vaimo\ComposerPatches;

class Plugin implements \Composer\Plugin\PluginInterface,
    \Composer\EventDispatcher\EventSubscriberInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Managers\AppliedPatchesManager
     */
    private $appliedPatchesManager;

    /**
     * @var \Vaimo\ComposerPatches\Factories\RepositoryManagerFactory
     */
    private $repositoryManagerFactory;

    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $this->appliedPatchesManager = new \Vaimo\ComposerPatches\Managers\AppliedPatchesManager();
        $this->repositoryManagerFactory = new \Vaimo\ComposerPatches\Factories\RepositoryManagerFactory();
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

        $repositoryManager = $this->repositoryManagerFactory->createForEvent($event);

        $repositoryManager->processRepository(
            $repository,
            $composer->getConfig()->get('vendor-dir')
        );
    }
}
