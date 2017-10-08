<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchPackagesResolverInterface
{
    /**
     * @param array $patches
     * @param array $packages
     * @return bool[]
     */
    public function resolve(array $patches, array $packages);
}
