<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\File;

use Vaimo\ComposerPatches\Config as PluginConfig;

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

        $patcherName = $this->processOperations($patchers, $sanityOperations);
        
        if (!$patcherName) {
            $message = sprintf(
                'None of the patch appliers seem to be available: %s',
                implode(', ', array_keys($patchers))
            );
            
            throw new \Vaimo\ComposerPatches\Exceptions\RuntimeException($message);
        }

        foreach ($levels as $patchLevel) {
            $arguments = array(
                PluginConfig::PATCHER_ARG_LEVEL => $patchLevel,
                PluginConfig::PATCHER_ARG_FILE => $filename,
                PluginConfig::PATCHER_ARG_CWD => $cwd
            );
            
            $patcherName = $this->processOperations(
                $patchers,
                $operations,
                $arguments,
                $failureMessages
            );
            
            if (!$patcherName) {
                continue;
            }

            $this->logger->writeVerbose(
                'info',
                'SUCCESS with type=%s (p=%s)',
                array($patcherName, $patchLevel)
            );

            return;
        }

        throw new \Exception(
            sprintf('Cannot apply patch %s', $filename)
        );
    }
    
    private function processOperations($patchers, array $operations, array $args = array(), array $failures = array())
    {
        list($operationName, $operationCode) = array_fill(0, 4, 'UNKNOWN');
        
        $variableFormats = array(
            '{{%s}}' => array('escapeshellarg'),
            '[[%s]]' => array()
        );

        foreach ($patchers as $type => $patcher) {
            $result = true;

            if (!$patcher) {
                continue;
            }

            $operationResults[$type] = array_fill_keys(array_keys($operations), '');

            foreach ($operations as $operationCode => $operationName) {
                if (!isset($patcher[$operationCode])) {
                    continue;
                }

                $args = array_replace($args, $operationResults[$type]);

                $applierOperations = is_array($patcher[$operationCode])
                    ? $patcher[$operationCode]
                    : array($patcher[$operationCode]);

                foreach ($applierOperations as $operation) {
                    $passOnFailure = strpos($operation, '!') === 0;
                    $operation = ltrim($operation, '!');

                    $command = $this->templateUtils->compose($operation, $args, $variableFormats);

                    $cwd = $this->extractStringValue($args, PluginConfig::PATCHER_ARG_CWD);
                    
                    $resultKey = $cwd . ' | ' . $command;

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
                        $result = $this->scanOutputForFailures(
                            $output,
                            $this->extractArrayValue($failures, $operationCode)
                        );
                    }

                    if ($passOnFailure) {
                        $result = !$result;
                    }

                    if (!$result) {
                        continue;
                    }

                    $operationResults[$type][$operationCode] = $output;

                    break;
                }

                if (!$result) {
                    break;
                }
            }

            if ($result) {
                return $type;
            }
            
            $messageArgs = array(
                is_string($operationName) ? $operationName : $operationCode,
                $type,
                $this->extractStringValue($args, PluginConfig::PATCHER_ARG_LEVEL)
            );
            
            $this->logger->writeVerbose('warning', '%s (type=%s) failed with p=%s', $messageArgs);
        }
        
        return '';
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
