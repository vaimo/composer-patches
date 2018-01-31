<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer;

class ResetOperation extends \Composer\DependencyResolver\Operation\InstallOperation
{
    public function __toString()
    {
        return 'Resetting patches for ' . $this->package->getPrettyName();
    }
}
