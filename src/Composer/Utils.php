<?php
namespace Vaimo\ComposerPatches\Composer;

use Composer\DependencyResolver\Operation;

class Utils
{
    public function getPackageFromOperation(Operation\OperationInterface $operation)
    {
        if ($operation instanceof Operation\InstallOperation) {
            return $operation->getPackage();
        } elseif ($operation instanceof Operation\UpdateOperation) {
            return $operation->getTargetPackage();
        }

        throw new \Exception(
            sprintf('Unknown operation: %s', get_class($operation))
        );
    }
}
