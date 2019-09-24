<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Exceptions;

class OperationFailure extends \Exception
{
    /**
     * @var array
     */
    private $output;
    
    public function setOutput($output)
    {
        $this->output = $output;
        
        return $this;
    }
    
    public function getOutput()
    {
        return $this->output;
    }
}
