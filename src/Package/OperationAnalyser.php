<?php
namespace Vaimo\ComposerPatches\Package;

use Composer\DependencyResolver\Operation\OperationInterface;

class OperationAnalyser
{
    public function isPatcherUninstallOperation(OperationInterface $operation)
    {
        if (!$operation instanceof \Composer\DependencyResolver\Operation\UninstallOperation) {
            return false;
        };
        
        $extra = $operation->getPackage()->getExtra();

        if (empty($extra[\Vaimo\ComposerPatches\Config::PATCHER_PLUGIN_MARKER])) {
            return false;
        }

        if (getenv(\Vaimo\ComposerPatches\Environment::NO_CLEANUP)) {
            return false;
        }
        
        return true;
    }
}
