<?php
namespace Vaimo\ComposerPatches\Composer;

class ResetOperation extends \Composer\DependencyResolver\Operation\InstallOperation
{
    public function __toString()
    {
        return 'Resetting patches for ' . $this->package->getPrettyName();
    }
}
