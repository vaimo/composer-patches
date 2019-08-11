<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchFailureHandlerInterface
{
    /**
     * @param string|\Exception $error
     * @param string $path
     */
    public function execute($error, $path);
}
