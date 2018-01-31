<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SequenceItemComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
{
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
        $items = array();

        foreach ($data[PatchDefinition::SOURCE] as $source => $subItem) {
            $items[] = array(
                $data[PatchDefinition::LABEL],
                array_replace($subItem, array(
                    PatchDefinition::SOURCE => $source
                ))
            );
        }

        return $items;
    }
}
