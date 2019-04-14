<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\File;

class Loader
{
    public function loadWithNormalizedLineEndings($path)
    {
        return implode(
            PHP_EOL,
            array_map('rtrim', file($path))
        );
    }
}
