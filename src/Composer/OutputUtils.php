<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer;

class OutputUtils extends \Composer\IO\ConsoleIO
{
    public static function resetVerbosity(\Composer\IO\ConsoleIO $appIO, $verbosity)
    {
        $oldValue = $appIO->output->getVerbosity();

        if ($appIO->isVerbose()) {
            return $oldValue;
        }

        $appIO->output->setVerbosity($verbosity);

        return $oldValue;
    }
}
