<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchFailureHandlerInterface
{
    /**
     * @param string $message
     * @param string $path
     */
    public function execute($message, $path);
}
