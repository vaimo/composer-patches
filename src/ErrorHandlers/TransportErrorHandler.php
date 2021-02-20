<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\ErrorHandlers;

class TransportErrorHandler
{
    /**
     * @var bool
     */
    private $gracefulMode;

    public function __construct($gracefulMode)
    {
        $this->gracefulMode = $gracefulMode;
    }

    public function handleError($source, \Composer\Downloader\TransportException $exception)
    {
        $statusLabel = sprintf('ERROR %s', $exception->getCode());

        if (strpos($exception->getMessage(), 'configuration does not allow connections') !== false) {
            $docsUrl = 'https://github.com/vaimo/composer-patches/blob/master/docs/CONFIGURATION.md#%s';
            $subjectReference = 'allow-downloads-from-unsecure-locations';

            $message = sprintf(
                'Your configuration does not allow connections to %s. Override the \'secure-http\' to allow: %s',
                $source,
                sprintf($docsUrl, $subjectReference)
            );

            $exception = new \Composer\Downloader\TransportException(
                $message,
                $exception->getCode()
            );

            $statusLabel = 'UNSECURE';
        }

        if ($this->gracefulMode) {
            return $statusLabel;
        }

        throw $exception;
    }
}
