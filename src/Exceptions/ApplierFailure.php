<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Exceptions;

class ApplierFailure extends \Exception
{
    private $errors = array();

    public function setErrors(array $output)
    {
        $this->errors = $output;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
