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
     * @var \Vaimo\ComposerPatches\Console\OutputAnalyser
     */
    private $outputAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Factories\ApplierErrorFactory
     */
    private $applierErrorFactory;

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
        $this->outputAnalyser = new \Vaimo\ComposerPatches\Console\OutputAnalyser();
        $this->applierErrorFactory = new \Vaimo\ComposerPatches\Factories\ApplierErrorFactory();
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

        $operations = array_filter($operations, function ($item) {
            return $item !== false;
        });

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

        $errorGroups = array();

        foreach ($levels as $patchLevel) {
            $arguments = array_replace(
                $arguments,
                array(PluginConfig::PATCHER_ARG_LEVEL => $patchLevel)
            );

            try {
                $patcherName = $this->executeOperations($patchers, $operations, $arguments, $failureMessages);
            } catch (\Vaimo\ComposerPatches\Exceptions\ApplierFailure $exception) {
                $errorGroups[] = $exception->getErrors();
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
            array_reduce($errorGroups, 'array_merge_recursive', array())
        );

        throw $failure;
    }

    private function executeOperations(
        array $patchers,
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

        throw $this->applierErrorFactory->create($outputRecords);
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
            $operationFailures = $this->extractArrayValue($failures, $operationCode);
            $applierOperations = is_array($patcher[$operationCode])
                ? $patcher[$operationCode]
                : array($patcher[$operationCode]);

            list($result, $output) = $this->resolveOperationOutput($applierOperations, $args, $operationFailures);

            if ($output !== false) {
                $operationResults[$operationCode] = $output;
            }

            if ($result) {
                continue;
            }

            $failure = new \Vaimo\ComposerPatches\Exceptions\OperationFailure($operationCode);
            $failure->setOutput(explode(PHP_EOL, $output));

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

            $this->outputBehaviourContextInfo($command, $resultKey, $passOnFailure);

            if (!isset($this->resultCache[$resultKey])) {
                $this->resultCache[$resultKey] = $this->shell->execute($command, $cwd);
            }

            list($result, $output) = $this->resultCache[$resultKey];

            if ($result) {
                $this->validateOutput($output, $operationFailures);
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

    private function outputBehaviourContextInfo($command, $resultKey, $passOnFailure)
    {
        if ($passOnFailure) {
            $this->logger->writeVerbose(
                \Vaimo\ComposerPatches\Logger::TYPE_NONE,
                '<comment>***</comment> '
                . 'The expected result to execution is a failure'
                . '<comment>***</comment>'
            );
        }

        if (isset($this->resultCache[$resultKey])) {
            $this->logger->writeVerbose(
                \Vaimo\ComposerPatches\Logger::TYPE_NONE,
                sprintf('(using cached result for: %s = %s)', $command, reset($this->resultCache[$resultKey]))
            );
        }
    }

    private function validateOutput($output, $operationFailures)
    {
        $pathMarker = '\|\+\+\+\s(?P<match>.*?)(\t|$)';
        $pathMatcher = sprintf('/^%s/', $pathMarker);

        $failures = $this->outputAnalyser->scanOutputForFailures($output, $pathMatcher, $operationFailures);

        if (!$failures) {
            return;
        }

        foreach ($failures as $patternCode => $items) {
            foreach ($items as $index => $item) {
                if (preg_match($pathMatcher, $item)) {
                    continue;
                }

                $message = sprintf(
                    'Success changed to FAILURE due to output analysis (%s):',
                    $patternCode
                );

                $failures[$patternCode][$index] = implode(PHP_EOL, array($message, $item));

                $this->logger->writeVerbose(
                    'warning',
                    sprintf('%s: %s', $message, $operationFailures[$patternCode])
                );
            }
        }

        $failure = new \Vaimo\ComposerPatches\Exceptions\OperationFailure('Output analysis failed');

        throw $failure->setOutput(
            array_reduce($failures, 'array_merge', array())
        );
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
