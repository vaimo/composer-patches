<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Utils;

use Vaimo\ComposerPatches\Config;

class ConfigUtils
{
    public function mergeApplierConfig(array $config, array $updates)
    {
        $this->overrideValue($config, $updates, Config::PATCHER_SECURE_HTTP);
        $this->overrideValue($config, $updates, Config::PATCHER_FORCE_RESET);
        $this->overrideValue($config, $updates, Config::PATCHER_GRACEFUL);
        
        foreach (array_keys($config[Config::PATCHER_APPLIERS]) as $code) {
            if (!isset($updates[Config::PATCHER_APPLIERS][$code])) {
                continue;
            }

            $config[Config::PATCHER_APPLIERS][$code] = array_replace(
                $config[Config::PATCHER_APPLIERS][$code],
                $updates[Config::PATCHER_APPLIERS][$code]
            );
        }

        $this->mergeArrayValue($config, $updates, Config::PATCHER_SEQUENCE);

        if (isset($updates[Config::PATCHER_SOURCES])) {
            $config[Config::PATCHER_SOURCES] = $updates[Config::PATCHER_SOURCES]
                ? array_replace($config[Config::PATCHER_SOURCES], $updates[Config::PATCHER_SOURCES])
                : array();
        }

        if (isset($updates[Config::PATCHER_FAILURES])) {
            foreach ($updates[Config::PATCHER_FAILURES] as $code => $patterns) {
                $config[Config::PATCHER_FAILURES][$code] = array_replace(
                    $config[Config::PATCHER_FAILURES][$code],
                    $patterns
                );
            }
        }

        $this->mergeArrayValue($config, $updates, Config::PATCHER_OPERATIONS);
        
        foreach (array_keys($config[Config::PATCHER_SEQUENCE]) as $sequenceName) {
            $origin = strtok($sequenceName, ':');

            if ($origin === $sequenceName) {
                continue;
            }

            $config[$sequenceName] = $config[$origin];
        }

        $this->overrideValue($config, $updates, Config::PATCHER_LEVELS);

        return $config;
    }
    
    private function overrideValue(&$config, $update, $key)
    {
        if (!isset($update[$key])) {
            return;
        }
    
        $config[$key] = $update[$key];
    }

    private function mergeArrayValue(&$config, $update, $key)
    {
        $config[$key] = array_replace(
            $config[$key],
            isset($update[$key]) ? $update[$key] : array()
        );
    }

    public function sortApplierConfig(array $config)
    {
        $sequences = $config[Config::PATCHER_SEQUENCE];
        
        $sequencedConfigItems = array_keys($sequences);

        foreach ($sequencedConfigItems as $item) {
            if (!isset($config[$item])) {
                continue;
            }

            $config[$item] = array_replace(
                array_flip($sequences[$item]),
                array_intersect_key(
                    $config[$item],
                    array_flip($sequences[$item])
                )
            );
        }

        return $config;
    }
    
    public function validateConfig(array $config)
    {
        $patchers = $this->extractArrayValue($config, Config::PATCHER_APPLIERS);
        
        $sequenceConfig = $config[Config::PATCHER_SEQUENCE];

        $patcherSequence = array_filter(
            $this->extractArrayValue($sequenceConfig, Config::PATCHER_APPLIERS)
        );
        
        if ((empty($patcherSequence) || array_intersect_key($patchers, array_flip($patcherSequence)))
            && is_array($patchers) && array_filter($patchers)
        ) {
            return;
        }

        $message = 'No valid patchers defined';

        if (!empty($patcherSequence)) {
            $message = sprintf('No valid patchers found for sequence: %s', implode(',', $patcherSequence));
        }
        
        throw new \Vaimo\ComposerPatches\Exceptions\ConfigValidationException($message);
    }

    private function extractArrayValue($data, $key)
    {
        return isset($data[$key]) ? $data[$key] : array();
    }
}
