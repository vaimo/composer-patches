<?php
namespace Vaimo\ComposerPatches\Interfaces;

interface DefinitionExploderProcessorInterface
{
    /**
     * @param string $label
     * @param mixed $data
     * @return bool
     */
    public function shouldProcess($label, $data);
    
    /**
     * @param string $label
     * @param mixed $data
     * @return array
     */
    public function explode($label, $data);
}
