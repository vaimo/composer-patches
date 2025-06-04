<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\ExploderComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class LabelVersionConfigComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderComponentInterface
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
            return $this->valueAnalyser->isConstraint($data);
        }

        $dataKeys = array_keys($data);
        if (in_array(PatchDefinition::SOURCE, $dataKeys)) {
            return false;
        }

        return $this->valueAnalyser->isConstraint(reset($data));
    }

    public function explode($label, $data)
    {
        $items = array();

        $versions = is_array($data) ? $data : array($data);

        foreach ($versions as $version) {
            if (!$this->valueAnalyser->isConstraint($version)) {
                continue;
            }

            $items[] = array($label, array(PatchDefinition::VERSION => $version));
        }

        return $items;
    }
}
