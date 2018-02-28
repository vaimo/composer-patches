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
    private $components;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface[] $components
     */
    public function __construct(
        array $components
    ) {
        $this->components = $components;
    }

    public function process($label, $data)
    {
        if ($label == 'Fix: correctly protect tags with params from html escape (https://github.com/magento/magento2/issues/9223)' && count($data) > 1)  {
            $i = 0;
        }

        foreach ($this->components as $processor) {
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
