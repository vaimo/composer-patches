<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SourceResolver
{
    public function updateSourceDeclaration($label, $data)
    {
        if (!is_array($data)) {
            return array(
                PatchDefinition::SOURCE => (string)$data
            );
        } 
        
        if (isset($data[PatchDefinition::SOURCE])) {
            unset($data[PatchDefinition::URL]);

            return $data;
        } 
        
        if (isset($data[PatchDefinition::URL])) {
            $data[PatchDefinition::SOURCE] = $data[PatchDefinition::URL];

            unset($data[PatchDefinition::URL]);

            return $data;
        }

        $data[PatchDefinition::SOURCE] = strtolower(
            str_replace(' ', '-', preg_replace('/[^A-Za-z0-9- ]/', '', $label))
        );

        return $data;
    }
}
