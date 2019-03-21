<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\DependencyResolver\Operation\OperationInterface;

class OperationAnalyser
{
    /**
     * @var \Vaimo\ComposerPatches\Package\ConfigAnalyser 
     */
    private $configAnalyser;

    public function __construct() 
    {
        $this->configAnalyser = new \Vaimo\ComposerPatches\Package\ConfigAnalyser(); 
    }

    public function isPatcherUninstallOperation(OperationInterface $operation)
    {
        if (!$operation instanceof \Composer\DependencyResolver\Operation\UninstallOperation) {
            return false;
        }
        
        return $this->configAnalyser->ownsNamespace($operation->getPackage(), __NAMESPACE__);
    }
}
