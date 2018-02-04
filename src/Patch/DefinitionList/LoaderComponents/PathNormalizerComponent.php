<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PathNormalizerComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $packageInfoResolver;

    /**
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
    ) {
        $this->packageInfoResolver = $packageInfoResolver;
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot)
    {
        foreach ($patches as $targetPackage => &$packagePatches) {
            foreach ($packagePatches as &$data) {
                if ($data[PatchDefinition::URL]) {
                    continue;
                }
                
                $patchOwner = $data[PatchDefinition::OWNER];

                if (!isset($packagesByName[$patchOwner])) {
                    continue;
                }

                $path = $data[PatchDefinition::SOURCE];
                
                $ownerPath = $this->packageInfoResolver->getSourcePath($packagesByName[$patchOwner]);

                if (strpos($path, $vendorRoot) === 0) {
                    $path = trim(
                        substr($path, strlen($vendorRoot)),
                        DIRECTORY_SEPARATOR
                    );
                }
                
                $data[PatchDefinition::PATH] = $ownerPath . DIRECTORY_SEPARATOR . $path;
            }
        }

        return $patches;
    }
}
