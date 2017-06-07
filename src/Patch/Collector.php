<?php
namespace Vaimo\ComposerPatches\Patch;

use \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface;

class Collector
{
    /**
     * @var PatchSourceLoaderInterface[]
     */
    private $sourceLoaders;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionsProcessor
     */
    private $definitionsProcessor;

    /**
     * @param PatchSourceLoaderInterface[] $sourceLoaders
     */
    public function __construct(
        array $sourceLoaders
    ) {
        $this->sourceLoaders = $sourceLoaders;
        $this->definitionsProcessor = new \Vaimo\ComposerPatches\Patch\DefinitionsProcessor();
    }

    /**
     * @param \Composer\Package\PackageInterface[] $packages
     * @return array
     */
    public function gatherAllPatches(array $packages)
    {
        $allPatches = array();

        foreach ($packages as $patchOwner) {
            $extra = $patchOwner->getExtra();

            /** @var PatchSourceLoaderInterface[] $sourceLoaders */
            $sourceLoaders = array_intersect_key($this->sourceLoaders, $extra);

            foreach ($sourceLoaders as $key => $loader) {
                $patchesByTarget = $this->definitionsProcessor->normalizeDefinitions(
                    $loader->load($extra[$key])
                );

                foreach ($patchesByTarget as $target => $patches) {
                    if (!isset($allPatches[$target])) {
                        $allPatches[$target] = array();
                    }

                    foreach ($patches as $patch) {
                        $allPatches[$target][] = array_replace($patch, array(
                            'owner' => $patchOwner->getName(),
                            'owner_type' => $patchOwner->getType()
                        ));
                    }
                }
            }
        }

        return $allPatches;
    }
}
