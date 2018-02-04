<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

class CustomExcludeComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var string[]
     */
    private $skippedPackageFlags;

    /**
     * @param array $skippedPackageFlags
     */
    public function __construct(
        array $skippedPackageFlags
    ) {
        $this->skippedPackageFlags = array_flip($skippedPackageFlags);
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     */
    public function process(array $patches, array $packagesByName)
    {
        foreach ($patches as $targetPackageName => &$packagePatches) {
            if (!isset($this->skippedPackageFlags[$targetPackageName])) {
                continue;
            }

            $packagePatches = false;
        }

        return array_filter($patches);
    }
}
