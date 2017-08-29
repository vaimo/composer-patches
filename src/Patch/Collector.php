<?php
namespace Vaimo\ComposerPatches\Patch;

use Composer\Package\RootPackage;

use Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

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
    public function collect(array $packages)
    {
        $patchList = array();

        foreach ($packages as $patchOwner) {
            $extra = $patchOwner->getExtra();

            /** @var PatchSourceLoaderInterface[] $sourceLoaders */
            $sourceLoaders = array_intersect_key($this->sourceLoaders, $extra);

            foreach ($sourceLoaders as $key => $loader) {
                $patchesByTarget = $this->definitionsProcessor->normalizeDefinitions(
                    $loader->load($extra[$key])
                );

                if ($loader instanceof \Vaimo\ComposerPatches\Interfaces\PatchListUpdaterInterface) {
                    $patchesByTarget = $loader->update($patchesByTarget);
                }

                foreach ($patchesByTarget as $target => $patches) {
                    if (!isset($patchList[$target])) {
                        $patchList[$target] = array();
                    }

                    foreach ($patches as $patch) {
                        $patchList[$target][] = array_replace($patch, array(
                            PatchDefinition::OWNER => $patchOwner->getName(),
                            PatchDefinition::OWNER_IS_ROOT => $patchOwner instanceof RootPackage,
                        ));
                    }
                }
            }
        }

        return $patchList;
    }
}
