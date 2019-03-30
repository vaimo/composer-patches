<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patcher;

use Vaimo\ComposerPatches\Config as Keys;

class ConfigReader
{
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface 
     */
    private $infoExtractor;

    /**
     * @param \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $infoExtractor
     */
    public function __construct(
        \Vaimo\ComposerPatches\Interfaces\PackageConfigExtractorInterface $infoExtractor
    ) {
        $this->infoExtractor = $infoExtractor;
    }
    
    public function readFromPackage(\Composer\Package\PackageInterface $package)
    {
        $config = array_filter(
            (array)$this->infoExtractor->getConfig($package, Keys::CONFIG_ROOT)
        );
        
        return $this->mirrorConfigValues($config, array(
            Keys::PATCHER_TARGETS => Keys::PATCHES_DEPENDS,
            Keys::PATCHER_BASE_PATHS => Keys::PATCHES_BASE,
            Keys::PATCHER_FILE_DEV => Keys::DEV_DEFINITIONS_FILE,
            Keys::PATCHER_FILE => Keys::DEFINITIONS_FILE,
            Keys::PATCHER_SEARCH => Keys::DEFINITIONS_SEARCH,
            Keys::PATCHER_SEARCH_DEV => Keys::DEV_DEFINITIONS_SEARCH
        ));
    }
    
    private function mirrorConfigValues(array $config, array $keyMap)
    {
        if (isset($config[Keys::PATCHER_CONFIG_ROOT])) {
            $patcherConfig = (array)$config[Keys::PATCHER_CONFIG_ROOT];

            foreach ($keyMap as $source => $target) {
                if (!isset($config[$target]) && isset($patcherConfig[$source])) {
                    $config[$target] = $patcherConfig[$source];
                }
            }
        }
        
        return $config;
    }
}
