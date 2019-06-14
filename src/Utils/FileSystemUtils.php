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
        if (!is_dir($rootPath)) {
            return array();
        }
        
        $directoryIterator = new \RecursiveDirectoryIterator($rootPath);
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

        array_multisort(
            array_keys($files),
            SORT_NATURAL | SORT_FLAG_CASE,
            $files
        );
        
        return $files;
    }
}
