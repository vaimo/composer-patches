<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class Normalizer
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface[]
     */
    private $components;

    /**
     * @var  \Vaimo\ComposerPatches\Patch\Definition\SourceResolver
     */
    private $sourceResolver;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface[] $components
     */
    public function __construct(
        array $components
    ) {
        $this->components = $components;
        $this->sourceResolver = new \Vaimo\ComposerPatches\Patch\Definition\SourceResolver();
    }

    public function process($target, $label, $data, array $ownerConfig)
    {
        $data = $this->sourceResolver->updateSourceDeclaration($label, $data);

        $config = array();

        foreach ($this->components as $component) {
            $updates = $component->normalize($target, $label, $data, $ownerConfig);
            
            if (!$updates) {
                continue;
            }

            $config = array_replace($config, $updates);
            $data = array_replace($data, $updates);
        }

        return $config;
    }
}
