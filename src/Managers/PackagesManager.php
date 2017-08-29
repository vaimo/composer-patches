<?php
namespace Vaimo\ComposerPatches\Managers;

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
     * @param \Composer\Package\RootPackageInterface $rootPackage
     * @param \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface[] $loaders
     * @param \Vaimo\ComposerPatches\Interfaces\PatchDefinitionProcessorInterface[] $processors
     */
    public function __construct(
        \Composer\Package\RootPackageInterface $rootPackage,
        array $loaders,
        array $processors
    ) {
        $this->patchesCollector = new \Vaimo\ComposerPatches\Patch\Collector($loaders);
        
        $this->rootPackage = $rootPackage;
        $this->processors = $processors;
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
    
    public function collectPatches(array $packages, $vendorRoot)
    {
        $rootName = $this->rootPackage->getName();
        $patches = $this->patchesCollector->collect($packages);

        if (isset($patches['*'])) {
            if (!isset($patches[$rootName])) {
                $patches[$rootName] = array();
            }

            $patches[$rootName] = array_merge($patches[$rootName], $patches['*']);
            unset($patches['*']);
        }

        foreach ($this->processors as $processor) {
            $patches = $processor->process($patches, $packages, $vendorRoot);
        }
        
        return $patches;
    }
}
