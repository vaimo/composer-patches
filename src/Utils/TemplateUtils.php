<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

class TemplateUtils
{
    public function compose($template, array $arguments, array $patterns)
    {
        $variables = array();

        foreach ($patterns as $format => $escaper) {
            $variables = array_replace(
                $variables,
                array_combine(
                    array_map(function ($item) use ($format) {
                        return sprintf($format, $item);
                    }, array_keys($arguments)),
                    $escaper ? array_map($escaper, $arguments) : $arguments
                )
            );
        }

        return str_replace(array_keys($variables), $variables, $template);
    }
}
