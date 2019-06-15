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
    
    public function __construct()
    {
        $this->valueAnalyser = new \Vaimo\ComposerPatches\Patch\Definition\Value\Analyser();
    }
    
    public function process($label, array $data)
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
