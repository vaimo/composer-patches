<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Shell
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Composer\Util\ProcessExecutor[]
     */
    private $processExecutors = array();

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $command
     * @param null|string $cwd
     * @return array
     */
    public function execute($command, $cwd = null)
    {
        if (strpos($command, '<') === 0) {
            $output = trim($command, '< ');

            return array(true, $output, $output);
        }

        $processExecutor = $this->getProcessExecutor();
        $logger = $this->logger;

        $output = '';
        $stdout = '';

        $outputHandler = function ($type, $data) use ($logger, &$output, &$stdout) {
            $output .= $data;
            if ($type !== 'err') {
                $stdout .= $data;
            }
            $logger->writeVerbose('comment', trim($data));
        };

        if ($this->logger->getOutputInstance()->isVerbose()) {
            $this->logger->writeIndentation();
        }

        $result = $processExecutor->execute($command, $outputHandler, $cwd);

        return array($result === 0, $output, $stdout);
    }

    private function getProcessExecutor()
    {
        $output = $this->logger->getOutputInstance();
        $isMutedFlag = (int)$this->logger->isMuted();

        if (!isset($this->processExecutors[$isMutedFlag])) {
            $this->processExecutors[$isMutedFlag] = new \Composer\Util\ProcessExecutor(
                $isMutedFlag ? null : $output
            );
        }

        return $this->processExecutors[$isMutedFlag];
    }
}
