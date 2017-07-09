<?php
namespace Vaimo\ComposerPatches\Factories;

class RepositoryManagerFactory
{
    public function createForEvent($event)
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $logger = new \Vaimo\ComposerPatches\Logger($io);

        $downloader = new \Composer\Util\RemoteFilesystem($io, $composer->getConfig());

        $patchesManager = new \Vaimo\ComposerPatches\Managers\PatchesManager(
            $composer->getEventDispatcher(),
            $downloader,
            $logger
        );

        $loaders = array(
            'patches' => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
            'patches-dev' => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchListDev(),
            'patches-file' => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
        );

        if (!$event->isDevMode()) {
            unset($loaders['patches-dev']);
        }

        return new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $composer->getInstallationManager(),
            $composer->getPackage(),
            $patchesManager,
            $logger,
            $loaders
        );
    }
}
