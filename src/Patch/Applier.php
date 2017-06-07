<?php
namespace Vaimo\ComposerPatches\Patch;

class Applier
{
    /**
     * @var \Vaimo\ComposerPatches\Shell
     */
    private $shell;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param array $patchers
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger,
        array $patchers
    ) {
        $this->logger = $logger;
        $this->patchers = $patchers;

        $this->shell = new \Vaimo\ComposerPatches\Shell($logger);
    }

    public function execute($filename, $cwd)
    {
        $result = false;
        $patchLevelSequence = array('1', '0', '2');

        $operationSequence = array(
            'validate' => 'Validation',
            'patch' => 'Patching'
        );

        list($type, $patchLevel, $operationName) = array_fill(0, 3, 'UNKNOWN');

        foreach ($this->patchers as $type => $patcher) {
            foreach ($patchLevelSequence as $sequenceIndex => $patchLevel) {
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

                if ($sequenceIndex >= count($patchLevelSequence) - 1) {
                    continue;
                }

                $this->logger->writeVerbose(
                    '%s (type=%s) failed with patch_level=%s. Retrying with patch_level=%s',
                    'warning',
                    array($operationName, $type, $patchLevel, $patchLevelSequence[$sequenceIndex + 1])
                );
            }
        }

        if ($result) {
            $this->logger->writeVerbose('SUCCESS with %s patch_level=%s', 'info', array($type, $patchLevel));
        } else {
            $this->logger->writeVerbose('FAILURE', 'error');
        }

        if (!$result) {
            throw new \Exception(sprintf('Cannot apply patch %s', $filename));
        }
    }
}
