<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionProcessors;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class CustomExcluder implements \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface
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

    public function process(array $patches, array $packagesByName, $vendorRoot)
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
