<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition;

class Exploder
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface[]
     */
    private $processors;
    
    /**
     * @param \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface[] $processors
     */
    public function __construct(
        array $processors
    ) {
        $this->processors = $processors;
    }
    
    public function process($label, $data)
    {
        foreach ($this->processors as $processor) {
            if (!$processor->shouldProcess($label, $data)) {
                continue;
            }

            if ($items = $processor->explode($label, $data)) {
                return $items;
            }
        }
        
        return array(
            array($label, $data)
        );
    }
}
