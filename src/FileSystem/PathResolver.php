<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\FileSystem;

use Vaimo\ComposerPatches\Utils\PathUtils;

class PathResolver
{
    /**
     * Find closest file path for specified file name while iterating upwards in the file-tree
     *
     * @param string $path
     * @param string $fileName
     * @return bool|string
     */
    public static function getClosestDirForFile($path, $fileName)
    {
        while (true) {
            if (\is_dir($path) && \file_exists(PathUtils::composePath($path, $fileName))) {
                return $path;
            }

            $parent = \dirname($path);

            if ($parent === $path) {
                break;
            }

            $path = $parent;
        }

        return false;
    }

    public static function getAncestorDirForFile($filePath, $fileName)
    {
        $path = $filePath;

        $rootPath = '';

        while (true) {
            if ($path === \DIRECTORY_SEPARATOR) {
                break;
            }

            if (\is_file(PathUtils::composePath($path, $fileName))) {
                $rootPath = $path;
            }

            $path = \dirname($path);
        }

        return $rootPath;
    }
}
