<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PlatformComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;

    /**
     * @var \Vaimo\ComposerPatches\Utils\ConstraintUtils
     */
    private $constraintUtils;

    /**
     * @var \Composer\Package\PackageInterface[]
     */
    private $constraintPackages;

    /**
     * @param \Composer\Package\PackageInterface[] $constraintPackages
     */
    public function __construct(
        array $constraintPackages = array()
    ) {
        $this->versionParser = new \Composer\Package\Version\VersionParser();
        $this->constraintUtils = new \Vaimo\ComposerPatches\Utils\ConstraintUtils();
        $this->constraintPackages = $constraintPackages;
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        /** @var \Composer\Package\CompletePackageInterface $rootPackage */
        $packages = $this->constraintPackages;
        
        foreach ($patches as &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                $patchConstraints = $patchData[PatchDefinition::DEPENDS];

                $patchConstraintsResults = array();

                foreach ($patchConstraints as $constraintTarget => &$version) {
                    if (!isset($packages[$constraintTarget])) {
                        continue;
                    }

                    $package = $packages[$constraintTarget];
                    $packageVersions = array($package->getVersion());

                    $matchResult = false;
                    
                    foreach (array_filter($packageVersions) as $packageVersion) {
                        $packageConstraint = $this->versionParser->parseConstraints($packageVersion);
                        $patchConstraint = $this->versionParser->parseConstraints($version);

                        $matchResult = $patchConstraint->matches($packageConstraint);
                        
                        if ($matchResult) {
                            break;
                        }
                    }

                    $patchConstraintsResults[$constraintTarget] = $matchResult;
                }
                
                if (!$patchConstraintsResults) {
                    continue;
                }

                if (count(array_filter($patchConstraintsResults)) !== count($patchConstraintsResults)) {
                    $patchData = false;
                } else {
                    $patchData[PatchDefinition::DEPENDS] = array_diff_key(
                        $patchData[PatchDefinition::DEPENDS],
                        array_filter($patchConstraintsResults)
                    );
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
