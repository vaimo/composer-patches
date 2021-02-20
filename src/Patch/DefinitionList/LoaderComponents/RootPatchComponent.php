<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class RootPatchComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Composer\Package\PackageInterface
     */
    private $package;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    /**
     * @param \Composer\Package\PackageInterface $package
     */
    public function __construct(
        \Composer\Package\PackageInterface $package
    ) {
        $this->package = $package;

        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        $packageName = $this->package->getName();

        return $this->patchListUtils->applyDefinitionFilter(
            $patches,
            function ($patchData) use ($packageName) {
                if (!$patchData[PatchDefinition::LOCAL]) {
                    return true;
                }

                if ($packageName === $patchData[PatchDefinition::OWNER]) {
                    return true;
                }

                return false;
            }
        );
    }
}
