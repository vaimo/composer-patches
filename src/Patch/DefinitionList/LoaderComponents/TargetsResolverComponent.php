<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class TargetsResolverComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $packageInfoResolver;

    /**
     * @var \Vaimo\ComposerPatches\Patch\File\Analyser
     */
    private $patchFileAnalyser;

    /**
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
    ) {
        $this->packageInfoResolver = $packageInfoResolver;

        $this->patchFileAnalyser = new \Vaimo\ComposerPatches\Patch\File\Analyser();
    }

    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @return array
     * @throws \Vaimo\ComposerPatches\Exceptions\LoaderException
     */
    public function process(array $patches, array $packagesByName)
    {
        foreach ($patches as $patchTarget => $packagePatches) {
            foreach ($packagePatches as $index => $info) {
                $targets = isset($info[PatchDefinition::TARGETS])
                    ? $info[PatchDefinition::TARGETS]
                    : array();

                if (count($targets) > 1 || reset($targets) != PatchDefinition::BUNDLE_TARGET) {
                    continue;
                }

                $path = $info[PatchDefinition::PATH];

                if (!file_exists($path)) {
                    throw new \Vaimo\ComposerPatches\Exceptions\LoaderException(
                        sprintf('Could not resolve targets (file not found): %s ',  $info[PatchDefinition::SOURCE])
                    );

                    continue;
                }

                $paths = $this->patchFileAnalyser->getAllPaths(
                    file_get_contents($path)
                );

                if (!$targets = $this->packageInfoResolver->resolveNamesFromPaths($packagesByName, $paths)) {
                    throw new \Vaimo\ComposerPatches\Exceptions\LoaderException(
                        sprintf('Could not resolve targets (zero matches): %s ',  $info[PatchDefinition::SOURCE])
                    );
                }

                $patches[$patchTarget][$index][PatchDefinition::TARGETS] = array_unique($targets);
            }
        }

        return $patches;
    }
}
