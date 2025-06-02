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
     * @var \Vaimo\ComposerPatches\Patch\File\Loader
     */
    private $patchFileLoader;

    /**
     * @var \Vaimo\ComposerPatches\Patch\File\Analyser
     */
    private $patchFileAnalyser;

    /**
     * @var bool
     */
    private $gracefulMode;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     * @param bool $gracefulMode
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver,
        $gracefulMode = false
    ) {
        $this->packageInfoResolver = $packageInfoResolver;
        $this->gracefulMode = $gracefulMode;

        $this->patchFileLoader = new \Vaimo\ComposerPatches\Patch\File\Loader();
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
            $targets = $this->collectTargets($packagesByName, $packagePatches);

            foreach ($targets as $index => $items) {
                $patches[$patchTarget][$index][PatchDefinition::TARGETS] = $items;
            }
        }

        return $patches;
    }

    private function collectTargets($packagesByName, $packagePatches)
    {
        $result = array();

        foreach ($packagePatches as $index => $info) {
            $targets = isset($info[PatchDefinition::TARGETS])
                ? $info[PatchDefinition::TARGETS]
                : array();

            if (!in_array(PatchDefinition::BUNDLE_TARGET, $targets, true)) {
                continue;
            }

            if (count($targets) > 1) {
                continue;
            }

            $path = $info[PatchDefinition::PATH];
            $source = $info[PatchDefinition::SOURCE];

            if (!file_exists($path)) {
                throw $this->createError('patch file not found', $source);
            }

            $paths = $this->patchFileAnalyser->getAllPaths(
                $this->patchFileLoader->loadWithNormalizedLineEndings($path)
            );

            $bundleTargets = $this->packageInfoResolver->resolveNamesFromPaths($packagesByName, $paths);

            if (!$bundleTargets && !$this->gracefulMode) {
                throw $this->createError('zero matches', $source);
            }

            $result[$index] = array_unique($bundleTargets);
        }

        return $result;
    }

    private function createError($reason, $source)
    {
        return new \Vaimo\ComposerPatches\Exceptions\LoaderException(
            sprintf('Could not resolve targets (%s): %s ', $reason, $source)
        );
    }
}
