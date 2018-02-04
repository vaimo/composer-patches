<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

class InfoResolver
{
    const DEFAULT_PATH = '.';
    
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager
    ) {
        $this->installationManager = $installationManager;
    }
    
    public function getSourcePath(\Composer\Package\PackageInterface $package)
    {
        return !$package instanceof \Composer\Package\RootPackage
            ? $this->installationManager->getInstallPath($package)
            : realpath(dirname(\Composer\Factory::getComposerFile()));
    }
    
    public function resolveNamesFromPaths(array $packagesByName, array $paths)
    {
        $paths = array_unique(
            array_map('dirname', $paths)
        );

        $names = array();
        
        foreach ($paths as $path) {
            $segments = explode(DIRECTORY_SEPARATOR, $path);

            while ($chunk = array_slice($segments, 0, 2)) {
                array_shift($segments);

                $name = implode(DIRECTORY_SEPARATOR, $chunk);

                if (!isset($packagesByName[$name])) {
                    continue;
                }

                $names[] = $name;

                break;
            }
        }
        
        return $names;
    }
}
