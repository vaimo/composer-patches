<?php
namespace Vaimo\ComposerPatches\Patch;

class Analyser
{
    public function getAllPaths($contents)
    {
        $paths = array();
        
        $lines = explode("\n", $contents);
        
        foreach ($lines as $line) {
            $prefix = substr($line, 0, 4);

            if ($prefix != '--- ' && $prefix != '+++ ') {
                continue;
            }

            $paths[] = trim(substr(strtok($line, chr(9)), strlen($prefix)));
        }
        
        return $paths;
    }
}
