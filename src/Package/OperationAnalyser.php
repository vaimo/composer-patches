<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\DependencyResolver\Operation\OperationInterface;
use Vaimo\ComposerPatches\Plugin;

class OperationAnalyser
{
    public function isPatcherUninstallOperation(OperationInterface $operation)
    {
        if (!$operation instanceof \Composer\DependencyResolver\Operation\UninstallOperation) {
            return false;
        }

        return \in_array(
            Plugin::COMPOSER_PACKAGE,
            $operation->getPackage()->getNames(),
            true
        );
    }
}
