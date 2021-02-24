<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatchesProxy;

require_once 'src/Plugin.php';

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Plugin extends \Vaimo\ComposerPatches\Plugin
{
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $namespacePrefix = implode('\\', array_slice(explode('\\', get_parent_class($this)), 0, 2)) . '\\';
        $autoloadFile = $this->resolveAutoloadFilePath($composer);

        if (!$autoloadFile) {
            return;
        }

        include $autoloadFile;

        $composerCtxFactory = new \Vaimo\ComposerPatches\Factories\ComposerContextFactory($composer);
        $composerContext = $composerCtxFactory->create();

        $this->bootstrapFileTree($composerContext, $namespacePrefix);

        parent::activate($composer, $io);
    }

    public function resolveAutoloadFilePath(\Composer\Composer $composer)
    {
       /**
         * When running through the initial installation, make sure that installing the proxy
         * command (to get the patch commands) does not result in crashing the whole
         * installation process.
         */
        $autoloadFile = $this->composePath(
            $composer->getConfig()->get('vendor-dir'),
            'autoload.php'
        );

        if (!file_exists($autoloadFile)) {
            return '';
        }

        return $autoloadFile;
    }

    public function resetPackages(\Composer\Installer\PackageEvent $event)
    {
        $autoloadFile = $this->resolveAutoloadFilePath($event->getComposer());

        if (!$autoloadFile) {
            return;
        }

        return parent::resetPackages($event);
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        $autoloadFile = $this->resolveAutoloadFilePath($event->getComposer());

        if (!$autoloadFile) {
            return;
        }

        return parent::postInstall($event);
    }

    private function bootstrapFileTree(\Vaimo\ComposerPatches\Composer\Context $composerContext, $namespacePrefix)
    {
        $composer = $composerContext->getLocalComposer();

        $composerConfig = $composer->getConfig();

        $vendorDir = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);

        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($composer->getPackage())
        );

        $pluginPackage = $packageResolver->resolveForNamespace(
            $composerContext->getActivePackages(),
            $namespacePrefix
        );

        $this->createSymlink(
            realpath('.'),
            $this->composePath($vendorDir, $pluginPackage->getName()),
            true
        );
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function createSymlink($fromPath, $toPath, $graceful = false)
    {
        if (is_link($toPath)) {
            unlink($toPath);
        }

        if ($graceful && (file_exists($toPath) || !file_exists($fromPath))) {
            return;
        }

        symlink($fromPath, $toPath);
    }

    private function composePath()
    {
        $pathSegments = array_map(function ($item) {
            return rtrim($item, \DIRECTORY_SEPARATOR);
        }, func_get_args());

        return implode(
            DIRECTORY_SEPARATOR,
            array_filter($pathSegments)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function deactivate(\Composer\Composer $composer, \Composer\IO\IOInterface $appIO)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function uninstall(\Composer\Composer $composer, \Composer\IO\IOInterface $appIO)
    {
    }
}
