<?php
namespace Vaimo\ComposerPatches\Patch\DefinitionExploders;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class SequenceVersionItemExploder implements \Vaimo\ComposerPatches\Interfaces\DefinitionExploderProcessorInterface
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
        
        return is_numeric($label)
            && isset($data['label'], $data['source'])
            && is_array($data['source'])
            && !is_array(reset($data['source']))
            && $this->isConstraint(key($data['source']));
    }

    public function explode($label, $data)
    {
        $items = array();

        foreach ($data['source'] as $constraint => $source) {
            if (!$this->isConstraint($constraint)) {
                continue;
            }
            
            $items[] = array(
                $data['label'],
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
