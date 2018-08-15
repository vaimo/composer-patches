<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Strategies;

class BootstrapStrategy
{
    public function shouldAllow()
    {
        try {
            $input = new \Symfony\Component\Console\Input\ArgvInput();

            return !$input->hasParameterOption('--lock');
        } catch (\Exception $e) {
            // There are situations where composer is accessed from non-CLI entry-points,
            // which will cause $argv not to be available, resulting a crash.
        }

        return false;
    }
}
