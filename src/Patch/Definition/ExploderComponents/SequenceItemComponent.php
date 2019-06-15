<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SequenceItemComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
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
        
        return is_numeric($label)
            && isset($data[PatchDefinition::LABEL], $data[PatchDefinition::SOURCE])
            && is_array($data[PatchDefinition::SOURCE])
            && is_array(reset($data[PatchDefinition::SOURCE]));
    }

    public function explode($label, $data)
    {
        return $this->itemBuilder->createMultiple(
            $data[PatchDefinition::LABEL],
            $data,
            PatchDefinition::SOURCE
        );
    }
}
