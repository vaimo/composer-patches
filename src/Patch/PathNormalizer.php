<?php
namespace Vaimo\ComposerPatches\Patch;

class PathNormalizer
{
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager
    ) {
        $this->installationManager = $installationManager;
    }

    public function process(array $patches, array $packages, $vendorDir)
    {
        $packagesByName = array();

        foreach ($packages as $package) {
            $packagesByName[$package->getName()] = $package;
        }

        foreach ($patches as $targetPackage => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($patchData['owner_type'] == 'project') {
                    continue;
                }

                $patchOwner = $patchData['owner'];

                if (!isset($packagesByName[$patchOwner])) {
                    continue;
                }

                $patchOwnerPackage = $packagesByName[$patchOwner];
                $packageInstaller = $this->installationManager->getInstaller($patchOwnerPackage->getType());
                $patchOwnerPath = $packageInstaller->getInstallPath($patchOwnerPackage);

                $absolutePatchPath = $patchOwnerPath . '/' . $patchData['source'];

                if (strpos($absolutePatchPath, $vendorDir) === 0) {
                    $patchData['source'] = trim(substr($absolutePatchPath, strlen($vendorDir)), '/');
                }
            }
        }

        return $patches;
    }
}
