<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PackageResetStrategyInterface
{
    /**
     * @param \Composer\Package\PackageInterface $package
     * @return bool
     */
    public function shouldAllowReset(\Composer\Package\PackageInterface $package);
}
