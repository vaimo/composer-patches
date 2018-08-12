<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class FileSystemUtils
{
    public function collectPathsRecursively($rootPath, $pattern)
    {
        $directory = new \RecursiveDirectoryIterator($rootPath);
        $iterator = new \RecursiveIteratorIterator($directory);

        $regexIterator = new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

        $files = array();

        foreach ($regexIterator as $info) {
            $files[] = reset($info);
        }

        return $files;
    }
}
