<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

class ConfigReader
{
    /**
     * @var \Seld\JsonLint\JsonParser
     */
    private $jsonDecoder;

    public function __construct()
    {
        $this->jsonDecoder = new \Seld\JsonLint\JsonParser();
    }

    public function readToArray($source)
    {
        if (!file_exists($source)) {
            throw new \Vaimo\ComposerPatches\Exceptions\ReadException(
                sprintf('File not found: %s', $source)
            );
        }

        $sourceData = file_get_contents($source);
        
        if ($sourceData === false) {
            throw new \Vaimo\ComposerPatches\Exceptions\ReadException(
                sprintf('Failed to retrieve contents: %s', $source)
            );
        }

        return $this->jsonDecoder->parse(
            $sourceData,
            \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC
        );
    }
}
