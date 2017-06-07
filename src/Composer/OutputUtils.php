<?php
namespace Vaimo\ComposerPatches\Composer;

use Composer\DependencyResolver\Operation;

class OutputUtils extends \Composer\IO\ConsoleIO
{
    static public function resetVerbosity(\Composer\IO\ConsoleIO $io, $verbosity)
    {
        $oldValue = $io->output->getVerbosity();

        $io->output->setVerbosity($verbosity);

        return $oldValue;
    }
}
