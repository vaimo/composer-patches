<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\FailureHandlers;

class FatalHandler implements \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface
{
    public function execute($error, $path)
    {
        $isException = $error instanceof \Exception;

        throw new \Vaimo\ComposerPatches\Exceptions\PatchFailureException(
            $path,
            'Failed to apply the patch. Halting execution!',
            $isException ? $error : null
        );
    }
}
