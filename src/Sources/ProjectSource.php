<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Sources;

class ProjectSource implements \Vaimo\ComposerPatches\Interfaces\PatchSourceListInterface
{
    /**
     * @var \Composer\Package\RootPackageInterface
     */
    private $rootPackage;

    /**
     * @param \Composer\Package\RootPackageInterface $rootPackage
     */
    public function __construct(
        \Composer\Package\RootPackageInterface $rootPackage
    ) {
        $this->rootPackage = $rootPackage;
    }

    public function getItems(\Composer\Repository\WritableRepositoryInterface $repository)
    {
        return array($this->rootPackage);
    }
}
