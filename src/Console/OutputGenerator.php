<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Console;

class OutputGenerator
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;
    
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;
    }

    public function generateForException(\Exception $exception)
    {
        if ($exception instanceof \Vaimo\ComposerPatches\Exceptions\ApplierFailure) {
            $errors = array_filter($exception->getErrors());
            
            if (empty($errors)) {
                return;
            }

            $prioritizedErrors = $this->prioritizeErrors($errors);
            
            $lines = array_merge(
                array('Probable causes for the failure:', ''),
                $this->createOutputRows($prioritizedErrors),
                array('', '(For full, unfiltered details please use: composer patch:apply -vvv)')
            );

            $this->logger->writeNotice('warning', $lines);
        }
    }

    private function createOutputRows(array $errors)
    {
        $lines = array();

        foreach ($errors as $type => $groups) {
            if (!empty($lines)) {
                $lines[] = '';
            }

            $lines[] = sprintf('> %s', $type);

            foreach ($groups as $path => $messages) {
                $details = array(
                    '@' => $path,
                    '!' => reset($messages)
                );

                foreach (array_filter($details) as $marker => $value) {
                    foreach (explode(PHP_EOL, $value) as $index => $item) {
                        $lines[] = sprintf('  %s %s', !$index ? $marker : ' ', $item);
                    }
                }
            }
        }

        return $lines;
    }
    
    private function prioritizeErrors(array $errors)
    {
        $filters = array(
            'can\'t find file',
            'unable to find file',
            'no such file',
            'no file to patch'
        );

        $filterPattern = sprintf('/(%s)/i', implode('|', $filters));

        $result = array();

        foreach ($errors as $code => $groups) {
            $result[$code] = array();

            foreach ($groups as $path => $messages) {
                $messages = array_unique($messages);

                $fileNotFoundMessages = preg_grep($filterPattern, $messages);

                if ($fileNotFoundMessages !== $messages) {
                    $messages = array_merge(
                        array_diff($messages, $fileNotFoundMessages),
                        $fileNotFoundMessages
                    );
                }

                $result[$code][$path] = $messages;
            }
        }

        return array_map(function (array $items) {
            $filteredItems = array_filter($items);
            
            if (isset($filteredItems['']) && count($filteredItems) > 1) {
                unset($filteredItems['']);
            }
            
            return $filteredItems;
        }, $result);
    }
}
