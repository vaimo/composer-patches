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
        $result = false;
        
        list($type, $patchLevel, $operationName, $operationCode) = array_fill(0, 4, 'UNKNOWN');

        $applierConfig = $this->applierUtils->mergeApplierConfig($this->config, array_filter($config));
        
        $applierConfig = $this->applierUtils->sortApplierConfig($applierConfig);

        $patchers = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_APPLIERS);
        $operations = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_OPERATIONS);
        $levels = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_LEVELS);
        $failureMessages = $this->extractArrayValue($applierConfig, PluginConfig::PATCHER_FAILURES);
        
        try {
            $this->applierUtils->validateConfig($applierConfig);
        } catch (\Vaimo\ComposerPatches\Exceptions\ConfigValidationException $exception) {
            $this->logger->writeVerbose('error', $exception->getMessage());
        }
        
        $variableEscapers = array(
            '{{%s}}' => 'escapeshellarg',
            '[[%s]]' => false
        );

        $resultCache = array();
        
        foreach ($levels as $patchLevel) {
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
                    
                    $arguments = array_replace($operationResults[$type], array(
                        PluginConfig::PATCHER_ARG_LEVEL => $patchLevel,
                        PluginConfig::PATCHER_ARG_FILE => $filename,
                        PluginConfig::PATCHER_ARG_CWD => $cwd
                    ));

                    $applierOperations = is_array($patcher[$operationCode]) 
                        ? $patcher[$operationCode] 
                        : array($patcher[$operationCode]);
                    
                    foreach ($applierOperations as $operation) {
                        $passOnFailure = strpos($operation, '!') === 0;
                        $operation = ltrim($operation, '!');
                        
                        $command = $this->templateUtils->compose($operation, $arguments, $variableEscapers);

                        $resultKey = $cwd . '|' . $command;

                        if ($passOnFailure) {
                            $this->logger->writeVerbose(
                                \Vaimo\ComposerPatches\Logger::TYPE_NONE, 
                                '<comment>***</comment> ' 
                                . 'The expected result to execution is a failure'
                                . '<comment>***</comment>'
                            );
                        }

                        if (!isset($resultCache[$resultKey])) {
                            $resultCache[$resultKey] = $this->shell->execute($command, $cwd);
                        }

                        list($result, $output) = $resultCache[$resultKey];
                        
                        if ($result) {
                            $result = $this->scanOutputForFailures(
                                $output, 
                                $this->extractArrayValue($failureMessages, $operationCode)
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
                    break 2;
                }
                
                $this->logger->writeVerbose(
                    'warning',
                    '%s (type=%s) failed with p=%s',
                    array(
                        is_string($operationName) ? $operationName : $operationCode, 
                        $type, 
                        $patchLevel
                    )
                );
            }
        }

        if ($result) {
            $this->logger->writeVerbose(
                'info', 
                'SUCCESS with type=%s (p=%s)', 
                array($type, $patchLevel)
            );
        }

        if (!$result) {
            throw new \Exception(
                sprintf('Cannot apply patch %s', $filename)
            );
        }
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
        return isset($data[$key]) ? $data[$key] : array();
    }
}
