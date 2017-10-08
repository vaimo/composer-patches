<?php
namespace Vaimo\ComposerPatches\Patch\FailureHandlers;

class FatalHandler implements \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface
{
    public function execute($message)
    {
        throw new \Vaimo\ComposerPatches\Exceptions\PatchFailureException($message);
    }
}
