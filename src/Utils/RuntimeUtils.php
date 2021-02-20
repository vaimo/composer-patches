<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class RuntimeUtils
{
    public function setEnvironmentValues($values)
    {
        foreach ($values as $name => $value) {
            putenv($name . '=' . $value);
        }
    }

    public function executeWithPostAction($action, $postAction)
    {
        try {
            $result = $action();
        } catch (\Exception $exception) {
            $postAction();

            throw $exception;
        }

        $postAction();

        return $result;
    }
}
