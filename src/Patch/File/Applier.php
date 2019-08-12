<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\File;

use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Repository\PatchesApplier\Operation as PatcherOperation;

class Applier
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Vaimo\ComposerPatches\Shell
     */
    private $shell;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Vaimo\ComposerPatches\Utils\ConfigUtils
     */
    private $applierUtils;

    /**
     * @var \Vaimo\ComposerPatches\Utils\TemplateUtils
     */
    private $templateUtils;

    /**
     * @var array
     */
    private $resultCache;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param array $config
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger,
        array $config
    ) {
        $this->logger = $logger;
        $this->config = $config;

        $this->shell = new \Vaimo\ComposerPatches\Shell($logger);
        $this->applierUtils = new \Vaimo\ComposerPatches\Utils\ConfigUtils();
        $this->templateUtils = new \Vaimo\ComposerPatches\Utils\TemplateUtils();
    }

    public function applyFile($filename, $cwd, array $config = array())
    {
        $applierConfig = $this->applierUtils->mergeApplierConfig($this->config, array_filter($config));

        $applierConfig = $this->applierUtils->sortApplierConfig($applierConfig);

        $patchers = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_APPLIERS);
        $operations = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_OPERATIONS);
        $levels = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_LEVELS);
        $failureMessages = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_FAILURES);

        $sanityOperations = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_SANITY);

        try {
            $this->applierUtils->validateConfig($applierConfig);
        } catch (\Vaimo\ComposerPatches\Exceptions\ConfigValidationException $exception) {
            $this->logger->writeVerbose('error', $exception->getMessage());
        }

        $arguments = array(
            PluginConfig::PATCHER_ARG_FILE => $filename,
            PluginConfig::PATCHER_ARG_CWD => $cwd
        );

        $patcherName = $this->executeOperations($patchers, $sanityOperations, $arguments);

        if (!$patcherName) {
            $message = sprintf(
                'None of the patch appliers seem to be available (tried: %s)',
                implode(', ', array_keys($patchers))
            );

            throw new \Vaimo\ComposerPatches\Exceptions\RuntimeException($message);
        }
        
        $errors = array();

        foreach ($levels as $patchLevel) {
            $arguments = array_replace(
                $arguments,
                array(PluginConfig::PATCHER_ARG_LEVEL => $patchLevel)
            );

            try {
                $patcherName = $this->executeOperations($patchers, $operations, $arguments, $failureMessages);
            } catch (\Vaimo\ComposerPatches\Exceptions\ApplierFailure $exception) {
                $errors[] = $exception->getErrors();
                continue;
            }
            
            $this->logger->writeVerbose(
                'info',
                'SUCCESS with type=%s (p=%s)',
                array($patcherName, $patchLevel)
            );

            return;
        }
        
        $failure = new \Vaimo\ComposerPatches\Exceptions\ApplierFailure(
            sprintf('Cannot apply patch %s', $filename)
        );

        $failure->setErrors(
            $this->filterErrors($errors)
        );
        
        throw $failure;
    }
    
    private function filterErrors(array $errors)
    {
        $errors = array_map(
            'array_unique',
            array_reduce($errors, 'array_merge_recursive', array())
        );

        foreach ($errors as $type => $messages) {
            $fileNotFoundMessages = preg_grep('/(can\'t find file|unable to find file|no such file)/i', $messages);

            if ($fileNotFoundMessages !== $messages) {
                $errors[$type] = array_diff($messages, $fileNotFoundMessages);
            }
        }
        
        return array_map('array_filter', $errors);
    }

    private function executeOperations(
        $patchers,
        array $operations,
        array $args = array(),
        array $failures = array()
    ) {
        $outputRecords = array();
        
        foreach ($patchers as $type => $patcher) {
            if (!$patcher) {
                continue;
            }

            try {
                return $this->processOperationItems($patcher, $operations, $args, $failures);
            } catch (\Vaimo\ComposerPatches\Exceptions\OperationFailure $exception) {
                $operationReference = is_string($exception->getMessage())
                    ? $exception->getMessage()
                    : PatcherOperation::TYPE_UNKNOWN;

                $outputRecords[$type] = $exception->getOutput();
                    
                $messageArgs = array(
                    strtoupper($operationReference),
                    $type,
                    $this->extractStringValue($args, PluginConfig::PATCHER_ARG_LEVEL)
                );

                $this->logger->writeVerbose('warning', '%s (type=%s) failed with p=%s', $messageArgs);
            }
        }

        $failure = new \Vaimo\ComposerPatches\Exceptions\ApplierFailure();

        $failure->setErrors(
            $this->collectErrors($outputRecords)
        );

        throw $failure;
    }
    
    private function collectErrors(array $outputRecords)
    {
        $errors = array(
            'failed',
            'unexpected',
            'malformed',
            'error',
            'corrupt',
            'can\'t find file',
            'patch unexpectedly ends'
        );
        
        $errorMatcher = sprintf('/%s/i', implode('|', $errors));
        
        foreach ($outputRecords as $type => $output) {
            $messages = preg_grep('/^[^\|-]/i', explode(PHP_EOL, $output));
            $matches = preg_grep($errorMatcher, $messages);

            $outputRecords[$type] = reset($matches);
        }
        
        return $outputRecords;
    }

    private function processOperationItems($patcher, $operations, $args, $failures)
    {
        $operationResults = array_fill_keys(array_keys($operations), '');

        $result = true;

        foreach (array_keys($operations) as $operationCode) {
            if (!isset($patcher[$operationCode])) {
                continue;
            }

            $args = array_replace($args, $operationResults);

            $applierOperations = is_array($patcher[$operationCode])
                ? $patcher[$operationCode]
                : array($patcher[$operationCode]);


            $operationFailures = $this->extractArrayValue($failures, $operationCode);

            list($result, $output) = $this->resolveOperationOutput(
                $applierOperations,
                $args,
                $operationFailures
            );

            if ($output !== false) {
                $operationResults[$operationCode] = $output;
            }

            if ($result) {
                continue;
            }

            $failure = new \Vaimo\ComposerPatches\Exceptions\OperationFailure($operationCode);

            $failure->setOutput($output);
            
            throw $failure;
        }

        return $result;
    }

    private function resolveOperationOutput($applierOperations, $args, $operationFailures)
    {
        $variableFormats = array(
            '{{%s}}' => array('escapeshellarg'),
            '[[%s]]' => array()
        );

        $output = '';
        
        foreach ($applierOperations as $operation) {
            $passOnFailure = strpos($operation, '!') === 0;
            $operation = ltrim($operation, '!');

            $command = $this->templateUtils->compose($operation, $args, $variableFormats);

            $cwd = $this->extractStringValue($args, PluginConfig::PATCHER_ARG_CWD);

            $resultKey = sprintf('%s | %s', $cwd, $command);

            if ($passOnFailure) {
                $this->logger->writeVerbose(
                    \Vaimo\ComposerPatches\Logger::TYPE_NONE,
                    '<comment>***</comment> '
                    . 'The expected result to execution is a failure'
                    . '<comment>***</comment>'
                );
            }

            if (!isset($this->resultCache[$resultKey])) {
                $this->resultCache[$resultKey] = $this->shell->execute($command, $cwd);
            }

            list($result, $output) = $this->resultCache[$resultKey];

            if ($result) {
                $result = $this->scanOutputForFailures($output, $operationFailures);
            }

            if ($passOnFailure) {
                $result = !$result;
            }

            if (!$result) {
                continue;
            }

            return array($result, $output);
        }

        return array(false, $output);
    }

    private function scanOutputForFailures($output, array $failureMessages)
    {
        foreach ($failureMessages as $patternCode => $pattern) {
            if (!$pattern || !preg_match($pattern, $output)) {
                continue;
            }

            $this->logger->writeVerbose(
                'warning',
                sprintf(
                    'Success changed to FAILURE due to output analysis (%s): %s',
                    $patternCode,
                    $pattern
                )
            );

            return false;
        }

        return true;
    }

    private function extractArrayValue($data, $key)
    {
        return $this->extractValue($data, $key, array());
    }

    private function extractStringValue($data, $key)
    {
        return $this->extractValue($data, $key, '');
    }

    private function extractValue($data, $key, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
}
