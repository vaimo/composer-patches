<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList\Loader;

use Vaimo\ComposerPatches\Patch\DefinitionList\LoaderComponents;

use Vaimo\ComposerPatches\Config as PluginConfig;

class ComponentPool
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
     * @var bool[]|\Vaimo\ComposerPatches\Interfaces\DefinitionListLoaderComponentInterface[]
     */
    private $components = array();

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

    public function getList(PluginConfig $pluginConfig)
    {
        $rootPackage = $this->composer->getPackage();
        $extra = $rootPackage->getExtra();

        $installationManager = $this->composer->getInstallationManager();

        if (isset($extra['excluded-patches']) && !isset($extra[PluginConfig::EXCLUDED_PATCHES])) {
            $extra[PluginConfig::EXCLUDED_PATCHES] = $extra['excluded-patches'];
        }

        $excludes = isset($extra[PluginConfig::EXCLUDED_PATCHES])
            ? $extra[PluginConfig::EXCLUDED_PATCHES]
            : array();

        $composerConfig = clone $this->composer->getConfig();
        $downloader = new \Composer\Util\RemoteFilesystem($this->io, $composerConfig);

        $packageInfoResolver = new \Vaimo\ComposerPatches\Package\InfoResolver($installationManager);

        $defaults = array(
            'bundle' => new LoaderComponents\BundleComponent($rootPackage),
            'global-exclude' => $excludes ? new LoaderComponents\GlobalExcludeComponent($excludes) : false,
            'local-exclude' => new LoaderComponents\LocalExcludeComponent(),
            'custom-exclude' => new LoaderComponents\CustomExcludeComponent(
                $pluginConfig->getSkippedPackages()
            ),
            'path-normalizer' => new LoaderComponents\PathNormalizerComponent($packageInfoResolver),
            'constraints' => new LoaderComponents\ConstraintsComponent(),
            'downloader' => new LoaderComponents\DownloaderComponent($rootPackage, $downloader),
            'validator' => new LoaderComponents\ValidatorComponent(),
            'targets-resolver' => new LoaderComponents\TargetsResolverComponent($packageInfoResolver),
            'merger' => new LoaderComponents\MergerComponent(),
            'sorter' => new LoaderComponents\SorterComponent()
        );

        return array_values(
            array_filter(
                array_replace($defaults, $this->components)
            )
        );
    }

    public function registerComponent($name, $instance)
    {
        $this->components[$name] = $instance;
    }
}
