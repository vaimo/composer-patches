<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\Constraint;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class Exploder
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser
     */
    private $valueAnalyser;

    /**
     * @var PatchDefinition\Exploder\ItemBuilder
     */
    private $itemBuilder;

    public function __construct()
    {
        $this->valueAnalyser = new \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser();
        $this->itemBuilder = new \Vaimo\ComposerPatches\Patch\Definition\Exploder\ItemBuilder();
    }

    public function process($label, array $items, array $defaults = array())
    {
        $result = array();

        foreach ($items as $constraint => $source) {
            if (!$this->valueAnalyser->isConstraint($constraint)) {
                continue;
            }

            $result[] = $this->itemBuilder->createItem(
                $label,
                $defaults,
                array(
                    PatchDefinition::VERSION => $constraint,
                    PatchDefinition::SOURCE => $source
                )
            );
        }

        return $result;
    }
}
