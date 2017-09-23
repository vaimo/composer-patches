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
        if (is_array($data) && isset($data['label'])) {
            if ($data['label'] === 'Fix: Category tree items in admin get double-escaped due to ExtJs and Magento both doing the escaping') {
                $i = 0;
            }
        }
        
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
