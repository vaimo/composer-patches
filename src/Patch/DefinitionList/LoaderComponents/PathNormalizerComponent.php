<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PathNormalizerComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
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

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        foreach ($patches as $targetPackage => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($patchData[PatchDefinition::OWNER_IS_ROOT]) {
                    continue;
                }

                $patchOwner = $patchData[PatchDefinition::OWNER];

                if (!isset($packagesByName[$patchOwner])) {
                    continue;
                }

                $patchOwnerPackage = $packagesByName[$patchOwner];

                $packageInstaller = $this->installationManager->getInstaller($patchOwnerPackage->getType());
                $patchOwnerPath = $packageInstaller->getInstallPath($patchOwnerPackage);

                $absolutePatchPath = $patchOwnerPath . DIRECTORY_SEPARATOR . $patchData[PatchDefinition::SOURCE];

                if (strpos($absolutePatchPath, $vendorRoot) === 0) {
                    $patchData[PatchDefinition::SOURCE] = trim(
                        substr($absolutePatchPath, strlen($vendorRoot)),
                        DIRECTORY_SEPARATOR
                    );
                }
            }
        }

        return $patches;
    }
}
