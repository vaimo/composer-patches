<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Patch\Owner as PatchOwner;

class PathNormalizer
{
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

    public function process(array $patches, array $packages, $vendorDir)
    {
        foreach ($patches as $targetPackage => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($patchData[PatchDefinition::OWNER_TYPE] == PatchOwner::PROJECT) {
                    continue;
                }

                $patchOwner = $patchData[PatchDefinition::OWNER];

                if (!isset($packages[$patchOwner])) {
                    continue;
                }

                $patchOwnerPackage = $packages[$patchOwner];

                $packageInstaller = $this->installationManager->getInstaller($patchOwnerPackage->getType());
                $patchOwnerPath = $packageInstaller->getInstallPath($patchOwnerPackage);

                $absolutePatchPath = $patchOwnerPath . '/'
                    . $patchData[PatchDefinition::SOURCE];

                if (strpos($absolutePatchPath, $vendorDir) === 0) {
                    $patchData[PatchDefinition::SOURCE] = trim(
                        substr($absolutePatchPath, strlen($vendorDir)),
                        '/'
                    );
                }
            }
        }

        return $patches;
    }
}
