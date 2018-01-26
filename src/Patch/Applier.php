<?php
namespace Vaimo\ComposerPatches\Patch;

class Applier
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;
    
    /**
     * @var \Vaimo\ComposerPatches\Shell
     */
    private $shell;

    /**
     * @var array
     */
    private $patchers;
    
    /**
     * @var array
     */
    private $patchLevelSequence;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param array $patchers
     * @param array $patchLevelSequence
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger,
        array $patchers,
        array $patchLevelSequence
    ) {
        $this->logger = $logger;

        $this->patchers = $patchers;
        $this->patchLevelSequence = $patchLevelSequence;

        $this->shell = new \Vaimo\ComposerPatches\Shell($logger);
    }

    public function applyFile($filename, $cwd)
    {
        $result = false;

        $operationSequence = array(
            'validate' => 'Validation',
            'patch' => 'Patching'
        );

        list($type, $patchLevel, $operationName) = array_fill(0, 3, 'UNKNOWN');

        foreach ($this->patchLevelSequence as $sequenceIndex => $patchLevel) {
            foreach ($this->patchers as $type => $patcher) {
                $result = true;

                foreach ($operationSequence as $operationCode => $operationName) {
                    $result = $this->shell->execute($patcher[$operationCode], [$patchLevel, $filename], $cwd) 
                        && $result;

                    if (!$result) {
                        break;
                    }
                }

                if ($result) {
                    break 2;
                }

                if ($sequenceIndex >= count($this->patchLevelSequence) - 1) {
                    continue;
                }

                $this->logger->writeVerbose(
                    '%s (type=%s) failed with p=%s. Retrying with p=%s',
                    'warning',
                    array($operationName, $type, $patchLevel, $this->patchLevelSequence[$sequenceIndex + 1])
                );
            }
        }

        if ($result) {
            $this->logger->writeVerbose('SUCCESS with type=%s (p=%s)', 'info', array($type, $patchLevel));
        } else {
            $this->logger->writeVerbose('FAILURE', 'error');
        }

        if (!$result) {
            throw new \Exception(sprintf('Cannot apply patch %s', $filename));
        }
    }
}
