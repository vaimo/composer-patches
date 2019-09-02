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
            
            $lines = array();

            foreach ($this->prioritizeErrors($errors) as $type => $groups) {
                if (!empty($lines)) {
                    $lines[] = '';
                }

                $lines[] = sprintf('> %s', $type);
                
                foreach ($groups as $path => $messages) {
                    $lines[] = sprintf('  @ %s', $path);
                    $lines[] = sprintf('  ! %s', reset($messages));
                }
            }

            $lines = array_merge(array('Probable causes for the failure:', ''), $lines);

            $lines = array_merge($lines, array(
                '',
                '(For full, unfiltered details please use: composer patch:apply -vvv)'
            ));

            $this->logger->writeNotice('warning', $lines);
        }
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

        return array_map('array_filter', $result);
    }
}
