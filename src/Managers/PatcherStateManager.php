<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;
use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Composer\Constraint;

class PatcherStateManager
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer
     */
    private $patchListTransformer;
    
    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PatchListUtils
     */
    private $patchListUtils;

    public function __construct()
    {
        $this->patchListTransformer = new \Vaimo\ComposerPatches\Patch\DefinitionList\Transformer();
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
        $this->patchListUtils = new \Vaimo\ComposerPatches\Utils\PatchListUtils();
    }

    public function registerAppliedPatches(WritableRepositoryInterface $repository, array $patches)
    {
        $packages = array();

        $patchQueue = $this->patchListTransformer->createSimplifiedList(
            array($patches)
        );

        foreach ($patchQueue as $target => $patches) {
            if (!$package = $repository->findPackage($target, Constraint::ANY)) {
                continue;
            }

            /** @var \Composer\Package\CompletePackage $package */
            $package = $this->packageUtils->getRealPackage($package);

            $info = array_replace_recursive(
                $package->getExtra(),
                array(PluginConfig::APPLIED_FLAG => $patches)
            );

            $package->setExtra($info);

            $packages[] = $package;
        }

        foreach ($packages as $package) {
            $extra = $package->getExtra();

            ksort($extra[PluginConfig::APPLIED_FLAG]);

            $package->setExtra($extra);
        }
    }
}
