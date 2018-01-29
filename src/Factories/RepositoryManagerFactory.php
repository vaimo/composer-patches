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
     * @var \Vaimo\ComposerPatches\Utils\ApplierUtils
     */
    private $applierUtils;

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

        $this->applierUtils = new \Vaimo\ComposerPatches\Utils\ApplierUtils();
    }
    
    public function create($devMode = false, array $patcherConfigData = [])
    {
        $includeDevPatches = $devMode;

        $installationManager = $this->composer->getInstallationManager();
        $composerConfig = $this->composer->getConfig();
        $rootPackage = $this->composer->getPackage();
        $eventDispatcher = $this->composer->getEventDispatcher();
        
        $vendorRoot = $composerConfig->get('vendor-dir');
        
        $config = new \Vaimo\ComposerPatches\Config();
        
        $logger = new \Vaimo\ComposerPatches\Logger($this->io);
        $downloader = new \Composer\Util\RemoteFilesystem($this->io, $composerConfig);
        
        if ($config->shouldExitOnFirstFailure()) {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\FatalHandler($logger);    
        } else {
            $failureHandler = new \Vaimo\ComposerPatches\Patch\FailureHandlers\GracefulHandler($logger);
        }

        $applierConfig = array(
            'patchers' => array(
                'GIT' => array(
                    'check' => 'git apply -p{{level}} --check {{file}}',
                    'patch' => 'git apply -p{{level}} {{file}}'
                ),
                'PATCH' => array(
                    'check' => 'patch -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}',
                    'patch' => 'patch -p{{level}} --no-backup-if-mismatch < {{file}}'
                )
            ),
            'operations' => array(
                'check' => 'Validation', 
                'patch' => 'Patching'
            ),
            'sequence' => array(
                'patchers' => array('PATCH', 'GIT'),
                'operations' => array('check', 'patch')
            ),
            'levels' => array('0', '1', '2')
        );
        
        if (isset($patcherConfigData[PluginConfig::PATCHER_CONFIG]) 
            && is_array($patcherConfigData[PluginConfig::PATCHER_CONFIG])
        ) {
            $applierConfig = $this->applierUtils->mergeConfig(
                $applierConfig, 
                $patcherConfigData[PluginConfig::PATCHER_CONFIG]
            );
        }

        $applierConfig = $this->applierUtils->sortConfig($applierConfig);

        $patchApplier = new \Vaimo\ComposerPatches\Patch\Applier($logger, $applierConfig);
        
        $patchesManager = new \Vaimo\ComposerPatches\Managers\PatchesManager(
            $installationManager,
            $eventDispatcher,
            $downloader,
            $failureHandler,
            $logger,
            $patchApplier,
            $vendorRoot
        );

        $loaders = array(
            PluginConfig::LIST => 
                new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
            PluginConfig::FILE => 
                new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile($installationManager)
        );

        if ($includeDevPatches) {
            $loaders = array_replace($loaders, array(
                PluginConfig::DEV_LIST => 
                    new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
                PluginConfig::DEV_FILE => 
                    new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile($installationManager)
            ));
        }
        
        if ($config->shouldPreferOwnerPackageConfig()) {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\VendorConfigExtractor(
                $installationManager
            );
        } else {
            $infoExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\InstalledConfigExtractor();
        }

        $patchCollector = new \Vaimo\ComposerPatches\Patch\Collector(
            $infoExtractor, 
            $loaders
        );
        
        if ($config->shouldResetEverything()) {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\FullResetResolver();
        } else {
            $packagesResolver = new \Vaimo\ComposerPatches\Patch\PackageResolvers\MissingPatchesResolver();
        }
        
        $patchProcessors = array(
            new DefinitionProcessors\GlobalExcluder($patcherConfigData),
            new DefinitionProcessors\LocalExcluder(),
            new DefinitionProcessors\CustomExcluder($config->getSkippedPackages()),
            new DefinitionProcessors\PathNormalizer($installationManager),
            new DefinitionProcessors\ConstraintsApplier($patcherConfigData),
            new DefinitionProcessors\Validator(),
            new DefinitionProcessors\Simplifier(),
        );
        
        $packagesManager = new \Vaimo\ComposerPatches\Managers\PackagesManager(
            $rootPackage,
            $patchCollector,
            $patchProcessors,
            $vendorRoot
        );
        
        $patcherConfig = new \Vaimo\ComposerPatches\Patch\Config($patcherConfigData);

        $appliedPatchesManager = new \Vaimo\ComposerPatches\Managers\AppliedPatchesManager();
        
        return new \Vaimo\ComposerPatches\Managers\RepositoryManager(
            $installationManager,
            $rootPackage,
            $patcherConfig,
            $patchesManager,
            $appliedPatchesManager,
            $packagesManager,
            $packagesResolver,
            $logger
        );
    }
}
