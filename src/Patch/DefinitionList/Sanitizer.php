<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\DefinitionList;

class Sanitizer
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\DataUtils
     */
    private $dataUtils;

    public function __construct()
    {
        $this->dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();
    }
    
    public function getSanitizedList(array $patches)
    {
        $dataUtils = $this->dataUtils;

        return $this->dataUtils->walkArrayNodes(
            $patches,
            function (array $value) use ($dataUtils) {
                return $dataUtils->removeKeysByPrefix($value, '_');
            }
        );
    }
}
