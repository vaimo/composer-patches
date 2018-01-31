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

        $key = key($data);
        $value = reset($data);

        return $this->isConstraint($key) 
            && !isset($value[PatchDefinition::VERSION], $value[PatchDefinition::DEPENDS]);
    }
    
    public function explode($label, $data)
    {
        $items = array();
        
        foreach ($data as $constraint => $source) {
            if (!$this->isConstraint($constraint)) {
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
