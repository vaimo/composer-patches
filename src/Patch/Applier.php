<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch;

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
    }

    public function applyFile($filename, $cwd, $config = array())
    {
        $result = false;

        list($type, $patchLevel, $operationName) = array_fill(0, 3, 'UNKNOWN');

        $applierConfig = $this->applierUtils->mergeApplierConfig(
            $this->config, 
            array_filter($config)
        );

        $applierConfig = $this->applierUtils->sortApplierConfig($applierConfig);
        
        $patchers = isset($applierConfig[PluginConfig::PATCHER_PROVIDERS]) 
            ? array_filter($applierConfig[PluginConfig::PATCHER_PROVIDERS]) 
            : array();
        
        $operations = isset($applierConfig[PluginConfig::PATCHER_OPERATIONS]) 
            ? array_filter($applierConfig[PluginConfig::PATCHER_OPERATIONS]) 
            : array();
        
        $levels = isset($applierConfig[PluginConfig::PATCHER_LEVELS]) 
            ? $applierConfig[PluginConfig::PATCHER_LEVELS] 
            : array();

        $patcherSequence = $applierConfig[PluginConfig::PATCHER_SEQUENCE][PluginConfig::PATCHER_PROVIDERS];

        if (!$patchers) {
            $this->logger->writeVerbose(
                'error',
                sprintf(
                    'No valid patchers found with sequence: %s', 
                    implode(',', $patcherSequence)
                )
            );
        }
        
        foreach ($levels as $sequenceIndex => $patchLevel) {
            foreach ($patchers as $type => $patcher) {
                $result = true;
                
                foreach ($operations as $operationCode => $operationName) {
                    $args = array(
                        PluginConfig::PATCHER_ARG_LEVEL => $patchLevel,
                        PluginConfig::PATCHER_ARG_FILE => $filename
                    );
                    
                    $result = $this->shell->execute($patcher[$operationCode], $args, $cwd) && $result;

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
                    array($operationName, $type, $patchLevel)
                );
            }
        }

        if ($result) {
            $this->logger->writeVerbose('info', 'SUCCESS with type=%s (p=%s)', array($type, $patchLevel));
        }

        if (!$result) {
            throw new \Exception(sprintf('Cannot apply patch %s', $filename));
        }
    }
}
