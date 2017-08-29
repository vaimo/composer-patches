<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Patch\Owner as PatchOwner;

class PathNormalizer implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
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
    
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        foreach ($patches as $targetPackage => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($patchData[PatchDefinition::OWNER_TYPE] == PatchOwner::PROJECT) {
                    continue;
                }

                $patchOwner = $patchData[PatchDefinition::OWNER];

                if (!isset($packagesByName[$patchOwner])) {
                    continue;
                }

                $patchOwnerPackage = $packagesByName[$patchOwner];

                $packageInstaller = $this->installationManager->getInstaller($patchOwnerPackage->getType());
                $patchOwnerPath = $packageInstaller->getInstallPath($patchOwnerPackage);

                $absolutePatchPath = $patchOwnerPath . '/'
                    . $patchData[PatchDefinition::SOURCE];

                if (strpos($absolutePatchPath, $vendorRoot) === 0) {
                    $patchData[PatchDefinition::SOURCE] = trim(
                        substr($absolutePatchPath, strlen($vendorRoot)),
                        '/'
                    );
                }
            }
        }

        return $patches;
    }
}
