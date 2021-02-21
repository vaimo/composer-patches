<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Factories;

class ApplierErrorFactory
{
    /**
     * @var \Vaimo\ComposerPatches\Utils\DataUtils
     */
    private $dataUtils;

    public function __construct()
    {
        $this->dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();
    }

    public function create(array $outputRecords)
    {
        $phrases = array(
            'failed',
            'unexpected',
            'malformed',
            'error',
            'corrupt',
            'can\'t find file',
            'patch unexpectedly ends',
            'due to output analysis',
            'no file to patch',
            'seem to find a patch in there anywhere',
            'Only garbage was found in the patch input',
            'patch fragment without header at line'
        );

        $messages = $this->collectErrors($outputRecords, $phrases);
        $failure = new \Vaimo\ComposerPatches\Exceptions\ApplierFailure();
        $failure->setErrors($messages);

        return $failure;
    }

    private function collectErrors(array $outputRecords, array $filters)
    {
        $pathMarker = '\|\+\+\+\s(?P<match>.*?)(\t|$)';

        $errorMatcher = sprintf(
            '/(%s)/i',
            implode('|', array_merge($filters, array($pathMarker)))
        );

        $pathMatcher = sprintf('/^%s/i', $pathMarker);
        $result = array();

        foreach ($outputRecords as $code => $output) {
            $lines = is_array($output) ? $output : explode(PHP_EOL, $output);

            $result[$code] = $this->dataUtils->listToGroups(
                preg_grep($errorMatcher, $lines),
                $pathMatcher
            );
        }

        return $result;
    }
}
