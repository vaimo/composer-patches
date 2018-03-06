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

    public function __construct()
    {
        $this->valueAnalyser = new \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser();
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
        $items = array();

        $sources = $data[PatchDefinition::SOURCE];

        unset($data[PatchDefinition::SOURCE]);

        foreach ($sources as $constraint => $source) {
            if (!$this->valueAnalyser->isConstraint($constraint)) {
                continue;
            }

            $items[] = array(
                $label,
                array_replace(
                    $data,
                    array(
                        PatchDefinition::VERSION => $constraint,
                        PatchDefinition::SOURCE => $source
                    )
                )
            );
        }

        return $items;
    }
}
