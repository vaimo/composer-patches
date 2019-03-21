<?php declare(strict_types = 1);
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatchesProxy;

require_once 'src/Plugin.php';

class Plugin extends \Vaimo\ComposerPatches\Plugin
{
    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $namespacePrefix = implode('\\', array_slice(explode('\\', get_parent_class($this)), 0, 2)) . '\\';
        
        $composerConfig = $composer->getConfig();

        $vendorDir = $composerConfig->get('vendor-dir');

        $autoloadFilePath = $this->composePath($vendorDir, 'composer', 'autoload_psr4.php');

        /**
         * When running through the initial installation, make sure that installing the proxy 
         * command (to get the patch commands) does not result in crashing the whole installation 
         * process.
         */
        if (!file_exists($autoloadFilePath)) {
            return;
        }
        
        $this->bootstrapAutoloader(
            include($autoloadFilePath)
        );
        
        $this->bootstrapFileTree($composer, $namespacePrefix);

        parent::activate($composer, $io);
    }

    private function bootstrapFileTree(\Composer\Composer $composer, $namespacePrefix)
    {
        $composerConfig = $composer->getConfig();
        $repositoryManager = $composer->getRepositoryManager();
        
        $localRepository = $repositoryManager->getLocalRepository();

        $vendorDir = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);

        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            [$composer->getPackage()]
        );
        
        $pluginPackage = $packageResolver->resolveForNamespace($localRepository, $namespacePrefix);

        $this->createSymlink(
            realpath('.'),
            $this->composePath($vendorDir, $pluginPackage->getName()),
            true
        );
    }

    private function bootstrapAutoloader($namespaceConfig)
    {
        spl_autoload_register(function ($class) use ($namespaceConfig) {
            foreach ($namespaceConfig as $classPathPrefix => $sources) {
                if (strpos($class, $classPathPrefix) === false) {
                    continue;
                }

                $classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($classPathPrefix)));

                foreach ($sources as $source) {
                    $classSourcePath = $this->composePath(
                        $source,
                        sprintf('%s.php', $classPath)
                    );
                    
                    if (!file_exists($classSourcePath)) {
                        continue;
                    }

                    include $classSourcePath;
                    
                    return true;
                }
            }

            return false;
        });
    }

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
}
