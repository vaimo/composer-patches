<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Json;

class Decoder
{
    /**
     * @var \Seld\JsonLint\JsonParser
     */
    private $jsonLinter;

    public function __construct()
    {
        $this->jsonLinter = new \Seld\JsonLint\JsonParser();
    }

    public function decode($json)
    {
        return $this->jsonLinter->parse(
            $json,
            \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC
        );
    }
}
