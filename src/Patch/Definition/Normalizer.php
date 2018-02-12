<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition;

use Vaimo\ComposerPatches\Patch\Definition;

class Normalizer
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface[]
     */
    private $components;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface[] $components
     */
    public function __construct(
        array $components
    ) {
        $this->components = $components;
    }
    
    public function process($target, $label, $data, array $ownerConfig)
    {
        if (!is_array($data)) {
            $data = array(
                Definition::SOURCE => (string)$data
            );
        }

        if (!isset($data[Definition::URL]) && !isset($data[Definition::SOURCE])) {
            return false;
        }
        
        $config = array();
        
        foreach ($this->components as $component) {
            $updates = $component->normalize($target, $label, $data, $ownerConfig);
            
            $config = array_replace($config, $updates);
            $data = array_replace($data, $updates);
        }
        
        return $config;
    }
}
