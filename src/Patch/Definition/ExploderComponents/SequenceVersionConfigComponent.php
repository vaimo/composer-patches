<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SequenceVersionConfigComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser
     */
    private $valueAnalyser;

    public function __construct()
    {
        $this->valueAnalyser = new \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser();
    }

    public function shouldProcess($label, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        return is_numeric($label)
            && isset($data[PatchDefinition::LABEL], $data[PatchDefinition::SOURCE])
            && is_array($data[PatchDefinition::SOURCE])
            && !is_array(reset($data[PatchDefinition::SOURCE]))
            && $this->valueAnalyser->isConstraint(key($data[PatchDefinition::SOURCE]));
    }

    public function explode($label, $data)
    {
        $items = array();

        foreach ($data[PatchDefinition::SOURCE] as $constraint => $source) {
            if (!$this->valueAnalyser->isConstraint($constraint)) {
                continue;
            }

            $items[] = array(
                $data[PatchDefinition::LABEL],
                array(
                    PatchDefinition::VERSION => $constraint,
                    PatchDefinition::SOURCE => $source
                )
            );
        }

        return $items;
    }
}
