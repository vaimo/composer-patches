<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition\NormalizerComponents;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class UrlComponent implements \Vaimo\ComposerPatches\Interfaces\DefinitionNormalizerComponentInterface
{
    public function normalize($target, $label, array $data, array $ownerConfig)
    {
        $source = $data[PatchDefinition::SOURCE];

        $sourcePathInfo = parse_url($source);
        $sourceIncludesUrlScheme = isset($sourcePathInfo['scheme']) && $sourcePathInfo['scheme'];

        return array(
            PatchDefinition::URL => $sourceIncludesUrlScheme ? $source : false,
        );
    }
}
