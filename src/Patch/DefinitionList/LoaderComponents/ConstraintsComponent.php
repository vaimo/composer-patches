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
     * @var \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface 
     */
    private $configExtractor;
    
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;

    /**
     * @var \Vaimo\ComposerPatches\Utils\ConstraintUtils 
     */
    private $constraintUtils;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $configExtractor
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $configExtractor
    ) {
        $this->configExtractor = $configExtractor;
        $this->versionParser = new \Composer\Package\Version\VersionParser();
        $this->constraintUtils = new \Vaimo\ComposerPatches\Utils\ConstraintUtils();
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        $rootPackages = array_filter($packagesByName, function ($package) {
            return $package instanceof \Composer\Package\RootPackage;
        });

        /** @var \Composer\Package\CompletePackageInterface $rootPackage */
        $rootPackage = reset($rootPackages);

        $rootRequires = array_replace(
            $rootPackage->getRequires(), 
            $rootPackage->getDevRequires()
        );
        
        foreach ($patches as $target => &$packagePatches) {
            foreach ($packagePatches as &$patchData) {
                if ($target !== PatchDefinition::BUNDLE_TARGET && !isset($packagesByName[$target])) {
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

                    $packageVersion = $package->getVersion();
                    $packageVersions = [$package->getVersion()];
                    
                    if (isset($rootRequires[$constraintTarget])) {
                        /** @var \Composer\Package\CompletePackageInterface $targetRootPackage */
                        $targetRootPackage = $rootRequires[$constraintTarget];

                        preg_match('/.* as (.*)$/', $targetRootPackage->getPrettyConstraint(), $matches);
                        
                        if (isset($matches[1])) {
                            $packageVersions[] = $matches[1];   
                        }
                    }
                    
                    
                    if ($this->constraintUtils->isDevConstraint($packageVersion)) {
                        $definedVersion = $this->configExtractor->getConfig(
                            $package, 
                            'version'
                        );

                        $packageVersions[] = $definedVersion;
                    }

                    $matchResult = false;
                    
                    foreach (array_filter($packageVersions) as $packageVersion) {
                        $packageConstraint = $this->versionParser->parseConstraints($packageVersion);
                        $patchConstraint = $this->versionParser->parseConstraints($version);

                        $matchResult = $patchConstraint->matches($packageConstraint); 
                        if ($matchResult) {
                            break;
                        }
                    }

                    $patchConstraintsResults[] = $matchResult;
                }

                if (count($patchConstraints) !== count(array_filter($patchConstraintsResults))) {
                    $patchData = false;
                }
            }

            $packagePatches = array_filter($packagePatches);
        }

        return array_filter($patches);
    }
}
