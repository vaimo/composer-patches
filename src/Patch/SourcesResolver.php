<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch;

use Composer\Repository\WritableRepositoryInterface as PackageRepository;

use Vaimo\ComposerPatches\Interfaces\PatchSourceListInterface;

class SourcesResolver
{
    /**
     * @var PatchSourceListInterface[]
     */
    private $listSources;

    /**
     * @param PatchSourceListInterface[] $listSources
     */
    public function __construct(
        array $listSources
    ) {
        $this->listSources = $listSources;
    }

    public function resolvePackages(PackageRepository $repository)
    {
        $result = array_reduce(
            $this->listSources,
            function ($result, PatchSourceListInterface $listSource) use ($repository) {
                return array_merge($result, $listSource->getItems($repository));
            },
            array()
        );

        return array_unique($result);
    }
}
