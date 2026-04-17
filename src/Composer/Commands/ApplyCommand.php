<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

class ApplyCommand extends \Vaimo\ComposerPatches\Composer\Commands\PatchCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('patch:apply');

        $this->setDescription('Apply a patch or patches for certain package(s)');

        $definition = $this->getDefinition();
        $options = $definition->getOptions();

        unset($options['redo'], $options['undo']);

        $definition->setOptions($options);
    }
}
