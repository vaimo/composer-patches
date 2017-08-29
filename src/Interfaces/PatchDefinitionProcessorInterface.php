<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchDefinitionProcessorInterface
{
    /**
     * @param array $patches
     * @param array $packagesByName
     * @param string $vendorRoot
     * @return array
     */
    public function process(array $patches, array $packagesByName, $vendorRoot);
}
