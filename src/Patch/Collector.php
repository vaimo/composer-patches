<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch;

use Composer\Package\RootPackage;
use Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class Collector
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\ListNormalizer
     */
    private $listNormalizer;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface
     */
    private $infoExtractor;

    /**
     * @var PatchSourceLoaderInterface[]
     */
    private $sourceLoaders;

    /**
     * @param \Vaimo\ComposerPatches\Patch\ListNormalizer $listNormalizer
     * @param \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $infoExtractor
     * @param PatchSourceLoaderInterface[] $sourceLoaders
     */
    public function __construct(
        \Vaimo\ComposerPatches\Patch\ListNormalizer $listNormalizer,
        \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $infoExtractor,
        array $sourceLoaders
    ) {
        $this->listNormalizer = $listNormalizer;
        $this->infoExtractor = $infoExtractor;
        $this->sourceLoaders = $sourceLoaders;
    }

    /**
     * @param \Composer\Package\PackageInterface[] $packages
     * @return array
     */
    public function collect(array $packages)
    {
        $collection = array();

        foreach ($packages as $owner) {
            $config = $this->infoExtractor->getConfig($owner);

            /** @var PatchSourceLoaderInterface[] $sourceLoaders */
            $sourceLoaders = array_intersect_key($this->sourceLoaders, $config);
            $ownerConfig = array_diff_key($config, $this->sourceLoaders);

            $patchListCollection = array();
            foreach ($sourceLoaders as $key => $source) {
                $groups = $source->load($owner, $config[$key]);

                $patchListCollection = array_merge(
                    $patchListCollection,
                    array_map(function (array $group) use ($ownerConfig) {
                        return $this->listNormalizer->normalize($group, $ownerConfig);
                    }, $groups)
                );
            }

            $patches = array_reduce($patchListCollection, 'array_merge_recursive', array());

            foreach ($patches as $target => $items) {
                foreach ($items as $index => $patch) {
                    $collection[$target][] = array_replace($patch, array(
                        PatchDefinition::OWNER => $owner->getName(),
                        PatchDefinition::OWNER_IS_ROOT => ($owner instanceof RootPackage),
                    ));
                }
            }
        }

        return $collection;
    }
}
