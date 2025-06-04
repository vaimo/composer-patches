<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class FileSystemUtils
{
    public function collectFilePathsRecursively($rootPath, $pattern)
    {
        if (strpos($rootPath, '*') !== false || strpos($rootPath, '?') !== false) {
            $paths = $this->collectGlobs($rootPath, $pattern);

            return array_filter($paths, function ($item) {
                return is_file($item);
            });
        }

        $paths = $this->collectPathsRecursively($rootPath, $pattern);

        return array_filter($paths, function ($item) {
            return is_file($item);
        });
    }

    public function collectGlobs($rootPath, $pattern)
    {
        $iterator = new \GlobIterator($rootPath);

        $files = array();

        foreach ($iterator as $info) {
            if ($info->isDir()) {
                $files = array_merge($files, $this->collectPathsRecursively($info->getRealPath(), $pattern));
                continue;
            }

            if (!$info->isFile() || !preg_match($pattern, $info->getRealPath())) {
                continue;
            }

            $path = $info->getRealPath();
            $files[$path] = $path;
        }

        $sequence = array_keys($files);

        natsort($sequence);

        return array_replace(
            array_flip($sequence),
            $files
        );
    }

    public function collectPathsRecursively($rootPath, $pattern)
    {
        if (!is_dir($rootPath)) {
            return array();
        }

        $directoryIterator = new \RecursiveDirectoryIterator(
            $rootPath,
            \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
        );

        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);

        $filesIterator = new \RegexIterator(
            $recursiveIterator,
            $pattern,
            \RecursiveRegexIterator::GET_MATCH
        );

        $files = array();

        foreach ($filesIterator as $info) {
            $path = reset($info);
            $files[substr($path, strlen($rootPath) + 1)] = $path;
        }

        $sequence = array_keys($files);

        natsort($sequence);

        return array_replace(
            array_flip($sequence),
            $files
        );
    }
}
