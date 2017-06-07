<?php
namespace Vaimo\ComposerPatches\Patch;

class Constraints
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;

    /**
     * @param array $config
     */
    public function __construct(
        array $config
    ) {
        $this->config = $config;

        $this->versionParser = new \Composer\Package\Version\VersionParser();
    }

    public function apply($patches, $packages)
    {
        $packagesByName = array();
        foreach ($packages as $package) {
            $packagesByName[$package->getName()] = $package;
        }

        $extra = $this->config;

        if (isset($extra['excluded-patches'])) {
            foreach ($extra['excluded-patches'] as $patchOwner => $patchPaths) {
                if (!isset($excludedPatches[$patchOwner])) {
                    $excludedPatches[$patchOwner] = array();
                }

                $excludedPatches[$patchOwner] = array_flip($patchPaths);
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

                $owner = $patchData['owner'];
                $source = $patchData['source'];

                if (isset($excludedPatches[$owner][$source])) {
                    $patchData = false;
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
