<?php
namespace Vaimo\ComposerPatches\Patch;

class Applier
{
    /**
     * @var \Composer\Util\ProcessExecutor $executor
     */
    protected $executor;

    /**
     * @var \Composer\IO\IOInterface $io
     */
    protected $io;

    /**
     * @param \Composer\Util\ProcessExecutor $executor
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Util\ProcessExecutor $executor,
        \Composer\IO\IOInterface $io
    ) {
        $this->executor = $executor;
        $this->io = $io;
    }

    public function execute($filename, $cwd)
    {
        $patchApplied = false;
        $patchLevelSequence = array('1', '0', '2');

        foreach ($patchLevelSequence as $sequenceIndex => $patchLevel) {
            $patchValidated = $this->executeCommand('git apply --check -p%s %s', [$patchLevel, $filename], $cwd);

            if (!$patchValidated) {
                if ($sequenceIndex < count($patchLevelSequence) && $this->io->isVerbose()) {
                    $this->io->write(
                        sprintf(
                            '<comment>Validation failed with patch_level=%s. Continuing with patch_level=%s</comment>',
                            $patchLevel,
                            $patchLevelSequence[$sequenceIndex + 1]
                        )
                    );
                }
                continue;
            }

            $patchApplied = $this->executeCommand('git apply -p%s %s', [$patchLevel, $filename], $cwd);

            if ($patchApplied) {
                break;
            }
        }

        if (!$patchApplied) {
            foreach ($patchLevelSequence as $patchLevel) {
                $patchApplied = $this->executeCommand('patch -p%s --no-backup-if-mismatch < %s', [$patchLevel, $filename], $cwd);

                if ($patchApplied) {
                    break;
                }
            }
        }

        if (isset($hostname)) {
            unlink($filename);
        }

        if (!$patchApplied) {
            throw new \Exception(sprintf('Cannot apply patch %s', $filename));
        }
    }

    protected function executeCommand($commandTemplate, array $arguments, $cwd = null)
    {
        foreach ($arguments as $index => $argument) {
            $arguments[$index] = escapeshellarg($argument);
        }

        $command = vsprintf($commandTemplate, $arguments);

        $outputHandler = '';

        if ($this->io->isVerbose()) {
            $io = $this->io;

            $outputHandler = function ($type, $data) use ($io) {
                $io->write('<comment>' . trim($data) . '</comment>');
            };
        }

        return $this->executor->execute($command, $outputHandler, $cwd) == 0;
    }
}
