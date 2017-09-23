<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionExploders;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SequenceItemExploder implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderProcessorInterface
{
    public function shouldProcess($label, $data)
    {
        if (!is_array($data)) {
            return false;
        }
        
        return is_numeric($label) 
            && isset($data['label'], $data['source']) 
            && is_array($data['source'])
            && is_array(reset($data['source']));
    }

    public function explode($label, $data)
    {
        $items = array();

        foreach ($data['source'] as $source => $subItem) {
            $items[] = array(
                $data['label'],
                array_replace($subItem, array(
                    PatchDefinition::SOURCE => $source
                ))
            );
        }

        return $items;
    }
}
