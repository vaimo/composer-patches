<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface DefinitionExploderComponentInterface
{
    /**
     * @param string $label
     * @param mixed $data
     * @return bool
     */
    public function shouldProcess($label, $data);

    /**
     * @param string $label
     * @param mixed $data
     * @return array
     */
    public function explode($label, $data);
}
