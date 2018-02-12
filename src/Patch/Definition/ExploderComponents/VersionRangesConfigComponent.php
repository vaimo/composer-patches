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
     * @var \Composer\Semver\VersionParser
     */
    private $versionParser;

    public function __construct() 
    {
        $this->versionParser = new \Composer\Semver\VersionParser();
    }
    
    public function shouldProcess($label, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        if (!isset($data[PatchDefinition::VERSION]) || isset($value[PatchDefinition::DEPENDS])) {
            return false;
        }

        $version = $data[PatchDefinition::VERSION];
        
        if (!is_array($version)) {
            return false;
        }
        
        return $this->isConstraint(
            key($version)
        );
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
    
    private function isConstraint($value)
    {
        try {
            $this->versionParser->parseConstraints($value);
        } catch (\UnexpectedValueException $exception) {
            return false;
        }

        return true;
    }
}
