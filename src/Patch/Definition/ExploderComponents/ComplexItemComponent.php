<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class ComplexItemComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\Definition\Exploder\ItemBuilder
     */
    private $itemBuilder;
    
    public function __construct()
    {
        $this->itemBuilder = new \Vaimo\ComposerPatches\Patch\Definition\Exploder\ItemBuilder();
    }

    public function shouldProcess($label, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        $key = key($data);
        $value = reset($data);

        $versionKeySet = false;

        if (is_array($value)) {
            $versionKeySet = isset($value[PatchDefinition::VERSION])
                || isset($value[PatchDefinition::DEPENDS]);
        }

        return !is_numeric($key) && is_array($value) && $versionKeySet;
    }

    public function explode($label, $data)
    {
        return $this->itemBuilder->createMultiple($label, $data, PatchDefinition::SOURCE);
    }
}
