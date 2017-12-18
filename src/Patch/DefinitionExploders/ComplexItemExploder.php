<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionExploders;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class ComplexItemExploder implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderProcessorInterface
{
    public function shouldProcess($label, $data)
    {
        if (!is_array($data)) {
            return false;
        }
        
        $key = key($data);
        $value = reset($data);

        $versionKeySet = false;
        
        if (is_array($value)) {
            $versionKeySet = isset($value[PatchDefinition::VERSION]) || isset($value[PatchDefinition::DEPENDS]);
        }   
        
        return !is_numeric($key) && is_array($value) && $versionKeySet;
    }
    
    public function explode($label, $data)
    {
        $items = array();
        
        foreach ($data as $source => $subItem) {
            $items[] = array(
                $label,
                array_replace($subItem, array(
                    PatchDefinition::SOURCE => $source
                ))
            );
        }
        
        return $items;
    }
}
