<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

use Vaimo\ComposerPatches\Composer\ConfigKeys as Config;
use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Factories;
use Vaimo\ComposerPatches\Composer\Constraint;

class Bootstrap
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Vaimo\ComposerPatches\Factories\ConfigFactory
     */
    private $configFactory;

    /**
     * @var \Vaimo\ComposerPatches\Factories\PatchesLoaderFactory
     */
    private $loaderFactory;

    /**
     * @var \Vaimo\ComposerPatches\Factories\PatchesApplierFactory
     */
    private $applierFactory;

    /**
     * @var \Vaimo\ComposerPatches\Repository\Processor
     */
    private $repositoryProcessor;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool
     */
    private $loaderComponents;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\ListResolverInterface
     */
    private $listResolver;

    /**
     * @var \Vaimo\ComposerPatches\Strategies\OutputStrategy 
     */
    private $outputStrategy;
    
    /**
     * @var \Vaimo\ComposerPatches\Managers\LockerManager
     */
    private $lockerManager;

    /**
     * @var \Vaimo\ComposerPatches\Utils\DataUtils
     */
    private $dataUtils;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     * @param \Vaimo\ComposerPatches\Factories\ConfigFactory $configFactory
     * @param \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver
     * @param \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $io,
        \Vaimo\ComposerPatches\Factories\ConfigFactory $configFactory,
        \Vaimo\ComposerPatches\Interfaces\ListResolverInterface $listResolver = null,
        \Vaimo\ComposerPatches\Strategies\OutputStrategy $outputStrategy = null
    ) {
        $this->composer = $composer;
        $this->configFactory = $configFactory;
        
        $logger = new \Vaimo\ComposerPatches\Logger($io);

        $this->listResolver = $listResolver;
        
        $this->outputStrategy = $outputStrategy;
        
        $this->loaderComponents = new \Vaimo\ComposerPatches\Patch\DefinitionList\Loader\ComponentPool(
            $composer,
            $io
        );

        $this->loaderFactory = new Factories\PatchesLoaderFactory($composer);

        $this->applierFactory = new Factories\PatchesApplierFactory($composer, $logger);
        
        $this->repositoryProcessor = new \Vaimo\ComposerPatches\Repository\Processor($logger);

        $this->lockerManager = new \Vaimo\ComposerPatches\Managers\LockerManager($io);
        
        $this->dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();
    }

    public function preloadPluginClasses()
    {
        $installationManager = $this->composer->getInstallationManager();
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $composerConfig = $this->composer->getConfig();
        
        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($this->composer->getPackage())
        );

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $installationManager,
            $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR)
        );

        $sourcesPreloader = new \Vaimo\ComposerPatches\Package\SourcesPreloader($packageInfoResolver);
        
        $sourcesPreloader->preload(
            $packageResolver->resolveForNamespace($repository, __NAMESPACE__)
        );
    }
    
    public function applyPatches($devMode = false)
    {
        $this->applyPatchesWithConfig(
            $this->configFactory->create(),
            $devMode
        );
    }

    public function stripPatches($devMode = false)
    {
        $configSources = array(
            array(\Vaimo\ComposerPatches\Config::PATCHER_SOURCES => false)
        );
        
        $this->applyPatchesWithConfig(
            $this->configFactory->create($configSources),
            $devMode
        );
    }

    private function applyPatchesWithConfig(PluginConfig $config, $devMode = false)
    {
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();

        $patchesLoader = $this->loaderFactory->create($this->loaderComponents, $config, $devMode);
        
        $patchesApplier = $this->applierFactory->create(
            $config, 
            $this->listResolver,
            $this->outputStrategy
        );

        $this->repositoryProcessor->process($repository, $patchesLoader, $patchesApplier);
    }
    
    public function sanitizeLocker()
    {
        if (!$lockData = $this->lockerManager->readLockData()) {
            return;
        }
        
        $queriedPaths = array(
            implode('/', array(Config::PACKAGES, Constraint::ANY)),
            implode('/', array(Config::PACKAGES_DEV, Constraint::ANY))
        );
        
        $nodes = $this->dataUtils->getNodeReferencesByPaths($lockData, $queriedPaths);

        foreach ($nodes as &$node) {
            if (!isset($node[Config::CONFIG_ROOT][PluginConfig::APPLIED_FLAG])) {
                continue;
            }
            
            unset($node[Config::CONFIG_ROOT][PluginConfig::APPLIED_FLAG]);

            if ($node[Config::CONFIG_ROOT]) {
                continue;
            }

            unset($node[Config::CONFIG_ROOT]);
        }
        
        unset($node);
        
        $this->lockerManager->writeLockData($lockData);
    }
}
