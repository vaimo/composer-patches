<?php
namespace Vaimo\ComposerPatches\Patch;

class Constraints
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    protected $versionParser;

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

        $this->versionParser = new \Composer\Package\Version\VersionParser();
    }

    public function apply($patches)
    {
        $packageRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $packages = $packageRepository->getPackages();

        $packagesByName = array();
        foreach ($packages as $package) {
            $packagesByName[$package->getName()] = $package;
        }

        $extra = $this->composer->getPackage()->getExtra();

        if (isset($extra['excluded-patches'])) {
            foreach ($extra['excluded-patches'] as $patchOwner => $patches) {
                if (!isset($excludedPatches[$patchOwner])) {
                    $excludedPatches[$patchOwner] = array();
                }

                $excludedPatches[$patchOwner] = array_flip($patches);
            }
        }

        foreach ($patches as $targetPackageName => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if (!isset($packagesByName[$targetPackageName])) {
                    $patchData = false;
                    continue;
                }

                if ($patchData['version']) {
                    $targetPackage = $packagesByName[$targetPackageName];
                    $packageConstraint = $this->versionParser->parseConstraints($targetPackage->getVersion());
                    $patchConstraint = $this->versionParser->parseConstraints($patchData['version']);

                    if (!$patchConstraint->matches($packageConstraint)) {
                        $patchData = false;
                    }
                }

                if (isset($excludedPatches[$patchData['owner']][$patchData['source']])) {
                    $patchData = false;
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
