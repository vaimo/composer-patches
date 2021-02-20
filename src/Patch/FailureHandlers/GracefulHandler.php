<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\FailureHandlers;

class GracefulHandler implements \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Vaimo\ComposerPatches\Console\OutputGenerator
     */
    private $outputGenerator;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;

        $this->outputGenerator = new \Vaimo\ComposerPatches\Console\OutputGenerator($logger);
    }

    public function execute($error, $path)
    {
        $this->logger->write('error', 'Failed to apply the patch. Skipping!');

        if (!$error instanceof \Exception) {
            return;
        }

        $this->outputGenerator->generateForException($error);
    }
}
