<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class DefinitionExploder
{
    public function process($label, $data)
    {
        $explodedItems = array();

        if (is_array($data)) {
            $key = key($data);
            $value = reset($data);

            if (!is_numeric($key) && is_array($value) 
                && (isset($value[PatchDefinition::VERSION]) || $value[PatchDefinition::DEPENDS])
            ) {
                foreach ($data as $source => $subItem) {
                    $explodedItems[] = array(
                        $label,
                        array_replace($subItem, array(
                            PatchDefinition::SOURCE => $source
                        ))
                    );
                }
            }   
        }
        
        if (!$explodedItems) {
            $explodedItems[] = array($label, $data);   
        }
        
        return $explodedItems;
    }
}
