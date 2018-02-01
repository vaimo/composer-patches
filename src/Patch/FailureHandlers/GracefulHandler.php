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
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;
    }
    
    public function execute($message, $path)
    {
        $this->logger->write('error', 'Failed to apply the patch. Skipping!');
    }
}
