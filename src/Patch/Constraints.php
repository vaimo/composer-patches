<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

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

    public function apply(array $patches, array $packages)
    {
        if (isset($this->config['excluded-patches'])) {
            foreach ($this->config['excluded-patches'] as $patchOwner => $patchPaths) {
                if (!isset($excludedPatches[$patchOwner])) {
                    $excludedPatches[$patchOwner] = array();
                }

                $excludedPatches[$patchOwner] = array_flip($patchPaths);
            }
        }

        foreach ($patches as $targetPackageName => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($targetPackageName != '*' && !isset($packages[$targetPackageName])) {
                    $patchData = false;
                    continue;
                }

                $patchConstraints = $patchData[PatchDefinition::DEPENDS];
                $patchConstraintsResults = [];

                foreach ($patchConstraints as $constraintTarget => &$version) {
                    if (!isset($packages[$constraintTarget])) {
                        continue;
                    }

                    $package = $packages[$constraintTarget];

                    $packageConstraint = $this->versionParser->parseConstraints($package->getVersion());
                    $patchConstraint = $this->versionParser->parseConstraints($version);

                    $patchConstraintsResults[] = $patchConstraint->matches($packageConstraint);
                }

                if ($patchConstraints && !array_filter($patchConstraintsResults)) {
                    $patchData = false;
                }

                $owner = $patchData[PatchDefinition::OWNER];
                $source = $patchData[PatchDefinition::SOURCE];

                if (isset($excludedPatches[$owner][$source])) {
                    $patchData = false;
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
