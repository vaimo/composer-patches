<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class UrlComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\DataUtils
     */
    private $dataUtils;
    
    public function __construct()
    {
        $this->dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();
    }

    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        $source = $data[PatchDefinition::SOURCE];

        $pathInfo = parse_url($source);
        $includesScheme = isset($pathInfo['scheme']) && $pathInfo['scheme'];
        
        return array(
            PatchDefinition::URL => $includesScheme ? $source : false,
            PatchDefinition::CHECKSUM => $this->dataUtils->extractValue($data, 'sha1', '') ?: ''
        );
    }
}
