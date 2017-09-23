<?php
namespace Vaimo\ComposerPatches\Patch;

class DefinitionExploder
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\DefinitionExploderProcessorInterface[]
     */
    private $processors;
    
    /**
     * @param \Vaimo\ComposerPatches\Interfaces\DefinitionExploderProcessorInterface[] $processors
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
