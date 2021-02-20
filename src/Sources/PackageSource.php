<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Sources;

use Composer\Repository\WritableRepositoryInterface;
use Composer\Package\PackageInterface;

class PackageSource implements \Vaimo\ComposerPatches\Interfaces\PatchSourceListInterface
{
    /**
     * @var array
     */
    private $packages;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FilterUtils
     */
    private $filterUtils;

    /**
     * @param array $packages
     */
    public function __construct(
        array $packages = array()
    ) {
        $this->packages = $packages;

        $this->filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();
    }

    public function getItems(WritableRepositoryInterface $repository)
    {
        $packages = $repository->getPackages();

        if (empty($this->packages)) {
            return $packages;
        }

        $filter = $this->filterUtils->composeRegex($this->packages, '/');

        return array_filter(
            $packages,
            function (PackageInterface $package) use ($filter) {
                return preg_match($filter, $package->getName());
            }
        );
    }
}
