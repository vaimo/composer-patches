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
            $messages = array_filter($exception->getErrors());

            if (!$messages) {
                return;
            }

            $lines = array(
                'Most likely causes for the failure:',
                ''
            );

            foreach ($messages as $type => $errors) {
                $lines[] = sprintf('* %s: %s', $type, reset($errors));
            }

            $lines = array_merge($lines, array(
                '',
                '(For full, unfiltered details please use: composer patch:apply -vvv)'
            ));

            $this->logger->writeNotice('warning', $lines);
        }
    }
}
