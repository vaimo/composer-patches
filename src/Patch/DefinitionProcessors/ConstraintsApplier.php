<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Patch\Config as PatchConfig;

class ConstraintsApplier implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;
    
    public function __construct() 
    {
        $this->versionParser = new \Composer\Package\Version\VersionParser();
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot) 
    {
        foreach ($patches as $target => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($target != PatchConfig::BUNDLE_TARGET && !isset($packagesByName[$target])) {
                    $patchData = false;
                    continue;
                }

                $patchConstraints = $patchData[PatchDefinition::DEPENDS];
                $patchConstraintsResults = array();

                foreach ($patchConstraints as $constraintTarget => &$version) {
                    if (!isset($packagesByName[$constraintTarget])) {
                        continue;
                    }

                    $package = $packagesByName[$constraintTarget];

                    $packageConstraint = $this->versionParser->parseConstraints($package->getVersion());
                    $patchConstraint = $this->versionParser->parseConstraints($version);

                    $patchConstraintsResults[] = $patchConstraint->matches($packageConstraint);
                }

                if ($patchConstraints && !array_filter($patchConstraintsResults)) {
                    $patchData = false;
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
