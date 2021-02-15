<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\Loader;

use Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;
use Vaimo\ComposerPatches\Config as PluginConfig;
use Composer\Downloader\FileDownloader;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ComponentPool
{
    /**
     * @var \Vaimo\ComposerPatches\Composer\Context
     */
    private $composerContext;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $appIO;

    /**
     * @var bool[]|\Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface[]
     */
    private $components = array();

    /**
     * @var bool
     */
    private $gracefulMode;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Vaimo\ComposerPatches\Composer\Context $composerContext
     * @param \Composer\IO\IOInterface $appIO
     * @param bool $gracefulMode
     */
    public function __construct(
        \Vaimo\ComposerPatches\Composer\Context $composerContext,
        \Composer\IO\IOInterface $appIO,
        $gracefulMode = false
    ) {
        $this->composerContext = $composerContext;
        $this->appIO = $appIO;
        $this->gracefulMode = $gracefulMode;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getList(PluginConfig $pluginConfig)
    {
        $skippedPackages = $pluginConfig->getSkippedPackages();
        $patcherConfig = $pluginConfig->getPatcherConfig();
        $composer = $this->composerContext->getLocalComposer();
        $composerConfig = clone $composer->getConfig();        

        $composerConfig->merge(array(
            'config' => array('secure-http' => $patcherConfig[PluginConfig::PATCHER_SECURE_HTTP])
        ));

        $rootPackage = $composer->getPackage();
        $extra = $rootPackage->getExtra();

        if (isset($extra['excluded-patches']) && !isset($extra[PluginConfig::EXCLUDED_PATCHES])) {
            $extra[PluginConfig::EXCLUDED_PATCHES] = $extra['excluded-patches'];
        }

        $excludes = isset($extra[PluginConfig::EXCLUDED_PATCHES])
            ? $extra[PluginConfig::EXCLUDED_PATCHES]
            : array();

        $installationManager = $composer->getInstallationManager();
        $cache = null;

        if ($composerConfig->get('cache-files-ttl') > 0) {
            $cache = new \Composer\Cache(
                $this->appIO,
                $composerConfig->get('cache-files-dir'),
                'a-z0-9_./'
            );
        }

        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            $fileDownloader = new FileDownloader($this->appIO, $composerConfig, null, $cache);
        } else {
            $httpDownloader = $composer->getLoop()->getHttpDownloader();
            $fileDownloader = new FileDownloader($this->appIO, $composerConfig, $httpDownloader, null, $cache);
        }

        $vendorRoot = $composerConfig->get(\Vaimo\ComposerPatches\Composer\ConfigKeys::VENDOR_DIR);
        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver(
            $installationManager,
            $vendorRoot
        );
        $configExtractor = new \Vaimo\ComposerPatches\Package\ConfigExtractors\VendorConfigExtractor(
            $packageInfoResolver
        );
        $platformPackages = $this->resolveConstraintPackages($composerConfig);
        $packageResolver = new \Vaimo\ComposerPatches\Composer\Plugin\PackageResolver(
            array($composer->getPackage())
        );
        $packages = $this->composerContext->getActivePackages();
        $pluginPackage = $packageResolver->resolveForNamespace($packages, __NAMESPACE__);
        $consoleSilencer = new \Vaimo\ComposerPatches\Console\Silencer($this->appIO);

        $defaults = array(
            'bundle' => new LoaderComponents\BundleComponent($rootPackage),
            'global-exclude' => $excludes ? new LoaderComponents\GlobalExcludeComponent($excludes) : false,
            'local-exclude' => new LoaderComponents\LocalExcludeComponent(),
            'root-patch' => new LoaderComponents\RootPatchComponent($rootPackage),
            'custom-exclude' => new LoaderComponents\CustomExcludeComponent($skippedPackages),
            'path-normalizer' => new LoaderComponents\PathNormalizerComponent($packageInfoResolver),
            'platform' => new LoaderComponents\PlatformComponent($platformPackages),
            'constraints' => new LoaderComponents\ConstraintsComponent($configExtractor),
            'downloader' => new LoaderComponents\DownloaderComponent(
                $composer,
                $pluginPackage,
                $fileDownloader,
                $consoleSilencer,
                $vendorRoot,
                $this->gracefulMode
            ),
            'validator' => new LoaderComponents\ValidatorComponent(),
            'targets-resolver' => new LoaderComponents\TargetsResolverComponent($packageInfoResolver),
            'merger' => new LoaderComponents\MergerComponent(),
            'sorter' => new LoaderComponents\SorterComponent()
        );

        return array_values(
            array_filter(array_replace($defaults, $this->components))
        );
    }

    private function resolveConstraintPackages(\Composer\Config $composerConfig)
    {
        $platformOverrides = array_filter(
            (array)$composerConfig->get('platform')
        );

        if (!empty($platformOverrides)) {
            $platformOverrides = array();
        }

        $platformRepo = new \Composer\Repository\PlatformRepository(
            array(),
            $platformOverrides ?: array()
        );

        $platformPackages = array();

        foreach ($platformRepo->getPackages() as $package) {
            $platformPackages[$package->getName()] = $package;
        }

        return $platformPackages;
    }

    public function registerComponent($name, $instance)
    {
        $this->components[$name] = $instance;
    }
}
