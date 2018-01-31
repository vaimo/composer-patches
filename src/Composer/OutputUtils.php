<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer;

class OutputUtils extends \Composer\IO\ConsoleIO
{
    static public function resetVerbosity(\Composer\IO\ConsoleIO $io, $verbosity)
    {
        $oldValue = $io->output->getVerbosity();

        if ($io->isVerbose()) {
            return $oldValue;
        }

        $io->output->setVerbosity($verbosity);

        return $oldValue;
    }
}
