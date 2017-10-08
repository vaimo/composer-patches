<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchFailureHandlerInterface
{
    /**
     * @param $message
     * @return mixed
     */
    public function execute($message);
}
