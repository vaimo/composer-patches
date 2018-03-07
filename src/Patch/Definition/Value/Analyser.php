<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\Value;

class Analyser
{
    /**
     * @var \Composer\Semver\VersionParser
     */
    private $versionParser;

    public function __construct()
    {
        $this->versionParser = new \Composer\Semver\VersionParser();
    }

    public function isConstraint($value)
    {
        if (is_array($value)) {
            return false;
        }

        try {
            $this->versionParser->parseConstraints($value);
        } catch (\UnexpectedValueException $exception) {
            return false;
        }

        return true;
    }
}
