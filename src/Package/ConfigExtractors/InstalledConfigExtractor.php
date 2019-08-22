<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package\ConfigExtractors;

class InstalledConfigExtractor implements \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface
{
    public function getConfig(\Composer\Package\PackageInterface $package, $configKey)
    {
        $methodName = sprintf(
            'get%s',
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace('_', ' ', $configKey)
                )
            )
        );
        
        return $package->$methodName();
    }
}
