<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

class ConfigAnalyser
{
    /**
     * @var \Vaimo\ComposerPatches\Package\ConfigExtractors\NamespaceConfigExtractor
     */
    private $namespacesExtractor;

    public function __construct()
    {
        $this->namespacesExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\NamespaceConfigExtractor();
    }

    public function isPluginPackage(\Composer\Package\PackageInterface $package)
    {
        return $package->getType() === \Vaimo\ComposerPatches\Composer\ConfigKeys::COMPOSER_PLUGIN_TYPE;
    }
    
    public function ownsNamespace(\Composer\Package\PackageInterface $package, $namespace)
    {
        return (bool)array_filter(
            $this->namespacesExtractor->getConfig($package, ''),
            function ($item) use ($namespace) {
                return $item && strpos($namespace, rtrim($item, '\\')) === 0;
            }
        );
    }
}
