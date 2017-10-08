<?php
namespace Vaimo\ComposerPatches\Factories;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Patch\DefinitionProcessors;

class RepositoryManagerFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;
    }
    
    public function create($devMode = false)
    {
        $includeDevPatches = $devMode;

        $installationManager = $this->composer->getInstallationManager();
        $composerConfig = $this->composer->getConfig();
        $rootPackage = $this->composer->getPackage();
        $eventDispatcher = $this->composer->getEventDispatcher();
        
        $extraInfo = $rootPackage->getExtra();
        $vendorRoot = $composerConfig->get('vendor-dir');

        $config = new \Vaimo\ComposerPatches\Config();
        
        $logger = new \Vaimo\ComposerPatches\Logger($this->io);
        $downloader = new \Composer\Util\RemoteFilesystem($this->io, $composerConfig);
        
        if ($config->shouldExitOnFirstFailure()) {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\FatalHandler($logger);    
        } else {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\GracefulHandler($logger);
        }
        
        $patchesManager = new \Vaimo\ComposerPatches\Managers\PatchesManager(
            $eventDispatcher,
            $downloader,
            $failureHandler,
            $logger,
            $vendorRoot
        );

        $loaders = array(
            PluginConfig::LIST => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
            PluginConfig::FILE => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
        );

        if ($includeDevPatches) {
            $loaders = array_replace($loaders, array(
                PluginConfig::DEV_LIST => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
                PluginConfig::DEV_FILE => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
            ));
        }
        
        if ($config->shouldPreferOwnerPackageConfig()) {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\VendorConfigExtractor(
                $installationManager
            );
        } else {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\InstalledConfigExtractor();
        }

        $patchCollector = new \Vaimo\ComposerPatches\Patch\Collector($infoExtractor, $loaders);
        
        if ($config->shouldResetEverything()) {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\FullResetResolver();
        } else {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\MissingPatchesResolver();
        }
        
        $patchProcessors = array(
            new DefinitionProcessors\GlobalExcluder($extraInfo),
            new DefinitionProcessors\LocalExcluder(),
            new DefinitionProcessors\CustomExcluder($config->getSkippedPackages()),
            new DefinitionProcessors\PathNormalizer($installationManager),
            new DefinitionProcessors\ConstraintsApplier($extraInfo),
            new DefinitionProcessors\Validator(),
            new DefinitionProcessors\Simplifier(),
        );
        
        $packagesManager = new \Vaimo\ComposerPatches\Managers\PackagesManager(
            $rootPackage,
            $patchCollector,
            $patchProcessors,
            $vendorRoot
        );
        
        return new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $installationManager,
            $rootPackage,
            $patchesManager,
            $packagesManager,
            $packagesResolver,
            $logger
        );
    }
}
