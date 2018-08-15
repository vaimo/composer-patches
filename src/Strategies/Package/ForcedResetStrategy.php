<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Strategies\Package;

class ForcedResetStrategy implements \Vaimo\ComposerPatches\Interfaces\PackageResetStrategyInterface
{
    public function shouldAllowReset(\Composer\Package\PackageInterface $package)
    {
        return true;
    }
}
