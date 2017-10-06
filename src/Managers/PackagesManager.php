<?php
namespace Vaimo\ComposerPatches\Managers;

use Vaimo\ComposerPatches\Patch\Config as PatchConfig;

class PackagesManager
{
    /**
     * @var \Composer\Package\RootPackageInterface
     */
    private $rootPackage;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Collector
     */
    private $patchesCollector;
    
    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface[]
     */
    private $processors;

    /**
     * @var string
     */
    private $vendorRoot;

    /**
     * @param \Composer\Package\RootPackageInterface $rootPackage
     * @param \Vaimo\ComposerPatches\Patch\Collector $patchesCollector
     * @param \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface[] $processors
     * @param string $vendorRoot
     */
    public function __construct(
        \Composer\Package\RootPackageInterface $rootPackage,
        \Vaimo\ComposerPatches\Patch\Collector $patchesCollector,
        array $processors,
        $vendorRoot
    ) {
        $this->patchesCollector = $patchesCollector;
        $this->rootPackage = $rootPackage;
        $this->processors = $processors;
        $this->vendorRoot = $vendorRoot;
    }
    
    public function getPackagesByName(array $packages)
    {
        $packagesByName = array();

        $rootName = $this->rootPackage->getName();

        foreach ($packages as $package) {
            if ($package instanceof \Composer\Package\AliasPackage) {
                $package = $package->getAliasOf();
            }

            $packagesByName[$package->getName()] = $package;
        }

        $packagesByName[$rootName] = $this->rootPackage;
        
        return $packagesByName;
    }
    
    public function getPatches(array $packages)
    {
        $patches = $this->patchesCollector->collect($packages);
        $rootName = $this->rootPackage->getName();

        if (isset($patches[PatchConfig::BUNDLE_TARGET])) {
            if (!isset($patches[$rootName])) {
                $patches[$rootName] = array();
            }

            $patches[$rootName] = array_merge(
                $patches[$rootName], 
                $patches[PatchConfig::BUNDLE_TARGET]
            );
            
            unset($patches[PatchConfig::BUNDLE_TARGET]);
        }

        foreach ($this->processors as $processor) {
            $patches = $processor->process($patches, $packages, $this->vendorRoot);
        }
        
        return $patches;
    }
}
