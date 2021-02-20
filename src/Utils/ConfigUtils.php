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
        $config = $this->mergeAppliers($config, $updates);
        $config = $this->mergeSources($config, $updates);
        $config = $this->mergeCustomFailures($config, $updates);
        $config = $this->mergeSequence($config, $updates);

        $this->overrideValue($config, $updates, Config::PATCHER_LEVELS);
        $this->overrideValue($config, $updates, Config::PATCHER_SECURE_HTTP);
        $this->overrideValue($config, $updates, Config::PATCHER_FORCE_RESET);
        $this->overrideValue($config, $updates, Config::PATCHER_GRACEFUL);
        $this->mergeArrayValue($config, $updates, Config::PATCHER_OPERATIONS);

        return $config;
    }

    private function mergeSequence(array $config, array $updates)
    {
        $this->mergeArrayValue($config, $updates, Config::PATCHER_SEQUENCE);

        foreach (array_keys($config[Config::PATCHER_SEQUENCE]) as $sequenceName) {
            $origin = strtok($sequenceName, ':');

            if ($origin === $sequenceName) {
                continue;
            }

            $config[$sequenceName] = $config[$origin];
        }

        return $config;
    }

    private function mergeSources(array $config, array $updates)
    {
        if (!isset($updates[Config::PATCHER_SOURCES])) {
            return $config;
        }

        $config[Config::PATCHER_SOURCES] = $updates[Config::PATCHER_SOURCES]
            ? array_replace($config[Config::PATCHER_SOURCES], $updates[Config::PATCHER_SOURCES])
            : array();

        return $config;
    }

    private function mergeCustomFailures(array $config, array $updates)
    {
        if (!isset($updates[Config::PATCHER_FAILURES])) {
            return $config;
        }

        foreach ($updates[Config::PATCHER_FAILURES] as $code => $patterns) {
            $config[Config::PATCHER_FAILURES][$code] = array_replace(
                $config[Config::PATCHER_FAILURES][$code],
                $patterns
            );
        }

        return $config;
    }

    private function mergeAppliers(array $config, array $updates)
    {
        foreach ($config[Config::PATCHER_APPLIERS] as $code => $operations) {
            if (!isset($updates[Config::PATCHER_APPLIERS][$code])) {
                continue;
            }

            foreach ($updates[Config::PATCHER_APPLIERS][$code] as $operationCode => $operationConfig) {
                if (!is_array($operations[$operationCode])) {
                    $operations[$operationCode] = array(
                        'default' => $operations[$operationCode]
                    );
                }

                if (!is_array($operationConfig)) {
                    $operationConfig = array(
                        'default' => $operationConfig
                    );
                }

                $operations[$operationCode] = array_filter(
                    array_replace($operations[$operationCode], $operationConfig)
                );
            }

            $config[Config::PATCHER_APPLIERS][$code] = $operations;
        }

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
