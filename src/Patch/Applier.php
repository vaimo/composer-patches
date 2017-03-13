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
        $result = false;
        $patchLevelSequence = array('1', '0', '2');

        $patchers = array(
            'GIT' => array(
                'validate' => 'git apply --check -p%s %s',
                'patch' => 'git apply -p%s %s'
            ),
            'PATCH' => array(
                'validate' => 'patch -p%s --dry-run --no-backup-if-mismatch < %s',
                'patch' => 'patch -p%s --no-backup-if-mismatch < %s'
            )
        );

        $operationSequence = array(
            'validate' => 'Validation',
            'patch' => 'Patching'
        );

        $type = 'UNKNOWN';
        $patchLevel = 'UNKNOWN';
        $operationName = 'UNKNOWN';

        foreach ($patchers as $type => $patcher) {
            foreach ($patchLevelSequence as $sequenceIndex => $patchLevel) {
                $result = true;

                foreach ($operationSequence as $operationCode => $operationName) {
                    $result = $this->executeCommand($patcher[$operationCode], [$patchLevel, $filename], $cwd)
                        && $result;

                    if (!$result) {
                        break;
                    }
                }

                if ($result) {
                    break 2;
                }

                if ($this->io->isVerbose() && $sequenceIndex < count($patchLevelSequence) - 1) {
                    $this->io->write(
                        sprintf(
                            '<warning>%s (type=%s) failed with patch_level=%s. Retrying with patch_level=%s</warning>',
                            $operationName,
                            $type,
                            $patchLevel,
                            $patchLevelSequence[$sequenceIndex + 1]
                        )
                    );
                }
            }
        }

        if ($this->io->isVerbose()) {
            $this->io->write(sprintf('<info>SUCCESS with %s patch_level=%s</info>', $type, $patchLevel));
        } else {
            $this->io->write('<error>FAILURE</error>');
        }

        if (!$result) {
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
