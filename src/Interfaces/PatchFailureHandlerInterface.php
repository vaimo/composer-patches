<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Interfaces;

interface PatchFailureHandlerInterface
{
    /**
     * @param $message
     * @return mixed
     */
    public function execute($message);
}
