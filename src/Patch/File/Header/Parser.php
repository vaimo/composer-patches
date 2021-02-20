<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\File\Header;

class Parser
{
    public function parseContents($header)
    {
        $lines = explode(PHP_EOL, $header);

        $key = 'label';

        $data = array();

        foreach ($lines as $line) {
            if (strpos($line, '@') === 0) {
                $tag = strstr($line, ' ', true);

                if (!$tag) {
                    $tag = $line;
                }

                $key = ltrim($tag, '@');

                $line = ltrim(strstr($line, ' '), ' ');
            }

            if (!isset($data[$key])) {
                $data[$key] = array();
            }

            $data[$key][] = $line;
        }

        return array_diff_key($data, array(''));
    }
}
