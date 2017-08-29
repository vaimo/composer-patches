<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchDefinitionProcessorInterface
{
    /**
     * @param array $patches
     * @param \Composer\Package\PackageInterface[] $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot);
}
