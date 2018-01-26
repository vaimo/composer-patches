<?php
namespace Vaimo\ComposerPatches\Patch;

use Composer\Package\RootPackage;

use Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class Collector
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface
     */
    private $infoExtractor;
    
    /**
     * @var PatchSourceLoaderInterface[]
     */
    private $sourceLoaders;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionsProcessor
     */
    private $definitionsProcessor;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $infoExtractor
     * @param PatchSourceLoaderInterface[] $sourceLoaders
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $infoExtractor,
        array $sourceLoaders
    ) {
        $this->sourceLoaders = $sourceLoaders;
        $this->infoExtractor = $infoExtractor;

        $this->definitionsProcessor = new \Vaimo\ComposerPatches\Patch\DefinitionsProcessor();
    }

    /**
     * @param \Composer\Package\PackageInterface[] $packages
     * @return array
     */
    public function collect(array $packages)
    {
        $patchList = array();

        foreach ($packages as $owner) {
            $packageConfig = $this->infoExtractor->getConfig($owner);

            /** @var PatchSourceLoaderInterface[] $sourceLoaders */
            $sourceLoaders = array_intersect_key(
                $this->sourceLoaders, 
                $packageConfig
            );

            foreach ($sourceLoaders as $key => $loader) {
                $patchesByTarget = $this->definitionsProcessor->normalizeDefinitions(
                    $loader->load($packageConfig[$key])
                );
                
                if ($loader instanceof \Vaimo\ComposerPatches\Interfaces\PatchListUpdaterInterface) {
                    $patchesByTarget = $loader->update($patchesByTarget);
                }

                foreach ($patchesByTarget as $target => $patches) {
                    if (!isset($patchList[$target])) {
                        $patchList[$target] = array();
                    }

                    foreach ($patches as $patchDefinition) {
                        $patchList[$target][] = array_replace(
                            $patchDefinition,
                            array(
                                PatchDefinition::OWNER => $owner->getName(),
                                PatchDefinition::OWNER_IS_ROOT => ($owner instanceof RootPackage),
                            )
                        );
                    }
                }
            }
        }

        return $patchList;
    }
}
