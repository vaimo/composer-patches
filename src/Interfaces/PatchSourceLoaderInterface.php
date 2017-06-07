<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchSourceLoaderInterface
{
    /**
     * @param $source
     * @return array
     */
    public function load($source);
}
