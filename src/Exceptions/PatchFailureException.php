<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Exceptions;

class PatchFailureException extends \Exception
{
    /**
     * @var string
     */
    private $failedPatchPath;
    
    public function __construct($failedPatchPath, $message = '', \Throwable $previous = null) 
    { 
        parent::__construct($message, 0, $previous);

        $this->failedPatchPath = $failedPatchPath;
    }
    
    public function getFailedPatchPath()
    {
        return $this->failedPatchPath;
    }
}
