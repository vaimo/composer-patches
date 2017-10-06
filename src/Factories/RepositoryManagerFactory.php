<?php
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;

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
            PluginConfig::LIST => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
            PluginConfig::FILE => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
        );

        if ($event->isDevMode()) {
            $loaders = array_replace($loaders, array(
                PluginConfig::DEV_LIST => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
                PluginConfig::DEV_FILE => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
            ));
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
