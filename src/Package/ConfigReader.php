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

        try {
            $result = $this->jsonDecoder->parse($sourceData, \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC);
        } catch (\Vaimo\ComposerPatches\Exceptions\DecoderException $exception) {
            $message = sprintf('Failed to retrieve contents of %s', $source);

            throw new \Vaimo\ComposerPatches\Exceptions\ReadException($message, 0, $exception);
        }

        return $result;
    }
}
