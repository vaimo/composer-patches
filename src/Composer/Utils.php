<?php
namespace Vaimo\ComposerPatches\Composer;

class Utils
{
    public function getPackageFromOperation(\Composer\DependencyResolver\Operation\OperationInterface $operation)
    {
        if ($operation instanceof \Composer\DependencyResolver\Operation\InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof \Composer\DependencyResolver\Operation\UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            throw new \Exception(sprintf('Unknown operation: %s', get_class($operation)));
        }

        return $package;
    }
}