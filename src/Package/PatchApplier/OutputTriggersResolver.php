<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package\PatchApplier;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class OutputTriggersResolver
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;
    
    public function __construct() 
    {
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }
    
    public function resolveForPatches(array $patches)
    {
        $hasFilterMatches = (bool)$this->patchListUtils->applyDefinitionFilter(
            $patches,
            true,
            PatchDefinition::STATUS_MATCH
        );

        if ($hasFilterMatches) {
            return array(
                PatchDefinition::STATUS_MATCH
            );
        }

        return array(
            PatchDefinition::STATUS_NEW,
            PatchDefinition::STATUS_CHANGED
        );
    }
}
