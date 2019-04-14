<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class VersionRangesConfigComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
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

        if (!isset($data[PatchDefinition::VERSION]) || isset($data[PatchDefinition::DEPENDS])) {
            return false;
        }

        $version = $data[PatchDefinition::VERSION];

        if (!is_array($version)) {
            return false;
        }

        return $this->valueAnalyser->isConstraint(key($version));
    }

    public function explode($label, $data)
    {
        $items = array();

        foreach ($data[PatchDefinition::VERSION] as $version) {
            $items[] = array(
                $label,
                array_replace($data, array(
                    PatchDefinition::VERSION => $version,
                ))
            );
        }

        return $items;
    }
}
