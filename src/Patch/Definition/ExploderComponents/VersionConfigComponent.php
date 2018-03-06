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

    public function __construct()
    {
        $this->valueAnalyser = new \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser();
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
        $items = array();

        foreach ($data as $constraint => $source) {
            if (!$this->valueAnalyser->isConstraint($constraint)) {
                continue;
            }

            $items[] = array(
                $label,
                array(
                    PatchDefinition::VERSION => $constraint,
                    PatchDefinition::SOURCE => $source
                )
            );
        }

        return $items;
    }
}
