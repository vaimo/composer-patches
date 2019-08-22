<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;

class RedoCommand extends \Vaimo\ComposerPatches\Composer\Commands\PatchCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('patch:redo');

        $this->setDescription('Re-apply a patch or patches for certain package(s)');

        $definition = $this->getDefinition();
        $options = $definition->getOptions();

        unset($options['redo'], $options['undo']);

        $definition->setOptions($options);
    }

    protected function getBehaviourFlags(InputInterface $input)
    {
        $flags = parent::getBehaviourFlags($input);

        return array_replace(
            $flags,
            array('redo' => true)
        );
    }
}
