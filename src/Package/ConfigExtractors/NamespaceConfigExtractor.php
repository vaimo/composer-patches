<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package\ConfigExtractors;

use Vaimo\ComposerPatches\Composer\ConfigKeys as ComposerConfig;

class NamespaceConfigExtractor implements \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface
{
    public function getConfig(\Composer\Package\PackageInterface $package, $configKey)
    {
        $autoload = $package->getAutoload();

        if (!isset($autoload[ComposerConfig::PSR4_CONFIG])) {
            return array();
        }

        return array_keys($autoload[ComposerConfig::PSR4_CONFIG]);
    }
}
