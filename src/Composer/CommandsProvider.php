<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer;

class CommandsProvider implements \Composer\Plugin\Capability\CommandProvider
{
    public function getCommands()
    {
        return array(
            new \Vaimo\ComposerPatches\Composer\Commands\PatchCommand,
            new \Vaimo\ComposerPatches\Composer\Commands\RedoCommand,
            new \Vaimo\ComposerPatches\Composer\Commands\UndoCommand
        );
    }
}
