<?php
namespace Vaimo\ComposerPatches\Patch;

class PathNormalizer
{
    /**
     * @var \Composer\Composer $composer
     */
    protected $composer;

    /**
     * @param \Composer\Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    public function process($patches)
    {
        $packageRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $packages = $packageRepository->getPackages();

        $packagesByName = array();

        foreach ($packages as $package) {
            $packagesByName[$package->getName()] = $package;
        }

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $manager = $this->composer->getInstallationManager();

        foreach ($patches as $targetPackage => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($patchData['owner_type'] == 'project') {
                    continue;
                }

                if (!isset($packagesByName[$patchData['owner']])) {
                    continue;
                }

                $patchOwnerPackage = $packagesByName[$patchData['owner']];
                $packageInstaller = $manager->getInstaller($patchOwnerPackage->getType());
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
