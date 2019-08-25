<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class GroupVersionConfigComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser
     */
    private $valueAnalyser;

    /**
     * @var PatchDefinition\Constraint\Exploder
     */
    private $constraintExploder;

    public function __construct()
    {
        $this->valueAnalyser = new \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser();
        $this->constraintExploder = new \Vaimo\ComposerPatches\Patch\Definition\Constraint\Exploder();
    }

    public function shouldProcess($label, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        if (!isset($data[PatchDefinition::SOURCE])) {
            return false;
        }

        $source = $data[PatchDefinition::SOURCE];

        if (!is_array($source)) {
            return false;
        }

        $key = key($source);
        $value = reset($source);

        return $this->valueAnalyser->isConstraint($key)
            && !isset($data[PatchDefinition::DEPENDS], $data[PatchDefinition::VERSION])
            && (
                !is_array($value)
                || !isset($value[PatchDefinition::VERSION], $value[PatchDefinition::DEPENDS])
            );
    }

    public function explode($label, $data)
    {
        return $this->constraintExploder->process(
            $label,
            $data[PatchDefinition::SOURCE],
            array_diff_key($data, array(PatchDefinition::SOURCE => true))
        );
    }
}
