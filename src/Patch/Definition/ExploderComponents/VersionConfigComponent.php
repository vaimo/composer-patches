<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class VersionConfigComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser
     */
    private $valueAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Definition\Constraint\Exploder
     */
    private $definitionExploder;
    
    public function __construct()
    {
        $this->valueAnalyser = new \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser();
        $this->definitionExploder = new \Vaimo\ComposerPatches\Patch\Definition\Constraint\Exploder();
    }

    public function shouldProcess($label, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        $key = key($data);
        $value = reset($data);

        return !isset($value[PatchDefinition::VERSION], $value[PatchDefinition::DEPENDS]) &&
            $this->valueAnalyser->isConstraint($key);
    }

    public function explode($label, $data)
    {
        return $this->definitionExploder->process($label, $data);
    }
}
