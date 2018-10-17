<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;
use Vaimo\ComposerPatches\Config as PluginConfig;

class StateGenerator
{
    /**
     * @var \Vaimo\ComposerPatches\Package\Collector
     */
    private $packageCollector;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageListUtils
     */
    private $packageListUtils;

    /**
     * @param \Vaimo\ComposerPatches\Package\Collector $packageCollector
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\Collector $packageCollector
    ) {
        $this->packageCollector = $packageCollector;
        
        $this->packageListUtils = new \Vaimo\ComposerPatches\Utils\PackageListUtils();
    }

    public function generate(PackageRepository $repository)
    {
        $packages = $this->packageCollector->collect($repository);
        
        return $this->packageListUtils->extractDataFromExtra(
            $packages,
            PluginConfig::APPLIED_FLAG,
            array()
        );
    }
}
