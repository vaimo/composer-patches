<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class ConstraintsComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
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
                if ($target != PatchDefinition::BUNDLE_TARGET && !isset($packagesByName[$target])) {
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

                if (count($patchConstraints) != count(array_filter($patchConstraintsResults))) {
                    $patchData = false;
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
