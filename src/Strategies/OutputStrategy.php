<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Strategies;

class OutputStrategy
{
    /**
     * @var array
     */
    private $outputTriggers;

    /**
     * @param array $outputTriggers
     */
    public function __construct(
        $outputTriggers = array()
    ) {
        $this->outputTriggers = $outputTriggers;
    }

    public function shouldAllowForPatches(array $patches)
    {
        $muteTriggersMatcher = array_flip($this->outputTriggers);

        return (bool)array_filter(
            $patches,
            function (array $patch) use ($muteTriggersMatcher) {
                return array_filter(
                    array_intersect_key($patch, $muteTriggersMatcher)
                );
            }
        );
    }
}
