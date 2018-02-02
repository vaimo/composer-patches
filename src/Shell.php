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
     * @var \Composer\Util\ProcessExecutor
     */
    private $processExecutor;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;

        $this->processExecutor =  new \Composer\Util\ProcessExecutor($logger->getOutputInstance());
    }

    public function execute($command, $cwd = null)
    {
        $logger = $this->logger;

        $output = '';
        $outputHandler = function ($type, $data) use ($logger, &$output) {
            $output = $output . trim($data);
            $logger->writeVerbose('comment', trim($data));
        };
        
        if ($this->logger->getOutputInstance()->isVerbose()) {
            $this->logger->writeIndentation();
        }
        
        $result = $this->processExecutor->execute($command, $outputHandler, $cwd);

        return array($result == 0, $output);
    }
}
