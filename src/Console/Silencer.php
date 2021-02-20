<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Console;

use Symfony\Component\Console\Output\OutputInterface;

use Vaimo\ComposerPatches\Composer\OutputUtils;

class Silencer
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $appIO;

    /**
     * @param \Composer\IO\ConsoleIO $appIO
     */
    public function __construct(
        \Composer\IO\ConsoleIO $appIO
    ) {
        $this->appIO = $appIO;
    }

    public function applyToCallback(\Closure $callback)
    {
        if ($this->appIO->isVerbose()) {
            return $callback();
        }

        $verbosityLevel = OutputUtils::resetVerbosity($this->appIO, OutputInterface::VERBOSITY_QUIET);

        try {
            $result = $callback();
        } catch (\Exception $exception) {
            OutputUtils::resetVerbosity($this->appIO, $verbosityLevel);

            throw $exception;
        }

        OutputUtils::resetVerbosity($this->appIO, $verbosityLevel);

        return $result;
    }
}
