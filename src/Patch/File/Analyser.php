<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\File;

class Analyser
{
    const REMOVAL_MARKER = '--- ';
    const ADDITION_MARKER = '+++ ';

    public function getAllPaths($contents)
    {
        $paths = array();

        $lines = explode(PHP_EOL, $contents);

        foreach ($lines as $line) {
            $prefix = substr($line, 0, 4);

            if (!$this->isPatchTargetHeader($line)) {
                continue;
            }

            $paths[] = trim(
                substr(
                    strtok($line, chr(9)),
                    strlen($prefix)
                )
            );
        }

        return $paths;
    }

    public function getHeader($contents)
    {
        $header = array();

        $lines = explode(PHP_EOL, $contents);

        foreach ($lines as $line) {
            if ($this->isPatchTargetHeader($line)) {
                break;
            }

            $header[] = $line;
        }

        return implode(PHP_EOL, $header);
    }

    private function isPatchTargetHeader($line)
    {
        $prefix = substr($line, 0, 4);

        return $prefix === self::REMOVAL_MARKER || $prefix === self::ADDITION_MARKER;
    }
}
