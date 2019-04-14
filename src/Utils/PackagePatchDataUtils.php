<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class PackagePatchDataUtils
{
    public function shouldReinstall(array $applied, array $patches)
    {
        return $applied === true
            || array_diff_assoc($applied, $patches)
            || array_diff_assoc($patches, $applied);
    }
}
