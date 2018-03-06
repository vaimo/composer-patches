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
            return $this->isConstraint($data);
        }

        return $this->isConstraint(reset($data));
    }

    public function explode($label, $data)
    {
        $items = array();

        $versions = is_array($data) ? $data : array($data);

        foreach ($versions as $version) {
            if (!$this->isConstraint($version)) {
                continue;
            }

            $items[] = array(
                $label,
                array(PatchDefinition::VERSION => $version)
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
