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
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

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

        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }
    
    public function getPackagesByName(array $packages)
    {
        $packagesByName = array();
        
        foreach ($packages as $package) {
            $packagesByName[$package->getName()] = $this->packageUtils->getRealPackage($package);
        }

        $packagesByName[$this->rootPackage->getName()] = $this->rootPackage;
        
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
