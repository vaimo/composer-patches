<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class PathUtils
{
    public static function composePath()
    {
        $pathSegments = array_map(
            function ($item) {
                return rtrim($item, DIRECTORY_SEPARATOR);
            },
            func_get_args()
        );

        return implode(
            DIRECTORY_SEPARATOR,
            array_filter($pathSegments)
        );
    }

    public static function reducePathLeft($path, $target)
    {
        return ltrim(substr($path, strlen($target)), DIRECTORY_SEPARATOR);
    }
}
