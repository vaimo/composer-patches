<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Console;

class OutputAnalyser
{
    public function scanOutputForFailures($output, $separatorMatcher, array $failureMessages)
    {
        $patternsWithResults = array_filter(
            $failureMessages,
            function ($pattern) use ($output) {
                return $pattern && preg_match($pattern, $output);
            }
        );

        if (!$patternsWithResults) {
            return array();
        }

        $lines = explode(PHP_EOL, $output);

        $matches = array();

        foreach ($patternsWithResults as $patternCode => $pattern) {
            if (!isset($matches[$pattern])) {
                $matches[$patternCode] = array();
            }
            
            foreach ($lines as $line) {
                if (preg_match($separatorMatcher, $line)) {
                    $matches[$patternCode][] = $line;

                    continue;
                }

                if (!preg_match($pattern, $line)) {
                    continue;
                }

                $matches[$patternCode][] = $line;
            }
        }
        
        return $matches;
    }
}
