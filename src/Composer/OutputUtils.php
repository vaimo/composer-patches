<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer;

class OutputUtils extends \Composer\IO\ConsoleIO
{
    public static function resetVerbosity(\Composer\IO\ConsoleIO $cliIO, $verbosity)
    {
        $oldValue = $cliIO->output->getVerbosity();

        if ($cliIO->isVerbose()) {
            return $oldValue;
        }

        $cliIO->output->setVerbosity($verbosity);

        return $oldValue;
    }
}
