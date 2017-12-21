<?php
namespace Vaimo\ComposerPatches\Composer;

class CommandsProvider implements \Composer\Plugin\Capability\CommandProvider
{
    public function getCommands()
    {
        return array(new \Vaimo\ComposerPatches\Composer\Commands\PatchCommand);
    }
}
