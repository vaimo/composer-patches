<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\Definition;

use Vaimo\ComposerPatches\Patch\Definition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class Normalizer
{
    public function process($target, $label, $data)
    {
        if (!is_array($data)) {
            $data = array(
                Definition::SOURCE => (string)$data
            );
        }

        if (!isset($data[Definition::URL]) && !isset($data[Definition::SOURCE])) {
            return false;
        }
        
        $source = isset($data[Definition::URL])
            ? $data[Definition::URL]
            : $data[Definition::SOURCE];

        $sourceSegments = explode('#', $source);
        $lastSegment = array_pop($sourceSegments);
        
        if ($lastSegment === Definition::SKIP) {
            $source = implode('#', $sourceSegments);
            $data[Definition::SKIP] = true;
        }

        $patchPathInfo = parse_url($source);

        if (isset($patchPathInfo['scheme']) && $patchPathInfo['scheme']) {
            $data[Definition::URL] = $source;
        } else {
            $data[Definition::URL] = false;
        }
        
        $depends = array();
        
        $config = array_replace(
            array(PluginConfig::PATCHER_SEQUENCE => array(), PluginConfig::PATCHER_LEVELS => array()),
            isset($config[Definition::CONFIG])
                ? $config[Definition::CONFIG]
                : array()
        );
        
        if (isset($data[Definition::VERSION])) {
            if (is_array($data[Definition::VERSION])) {
                $depends = array_replace(
                    $depends, 
                    $data[Definition::VERSION]
                );
            } else {
                $depends = array_replace(
                    $depends, 
                    array($target => $data[Definition::VERSION])
                );
            }
        }
        
        if (isset($data[Definition::DEPENDS])) {
            $depends = array_replace(
                $depends, 
                $data[Definition::DEPENDS]
            );
        }

        if (isset($data[Definition::BEFORE]) && !is_array($data[Definition::BEFORE])) {
            $data[Definition::BEFORE] = array($data[Definition::BEFORE]);
        }

        if (isset($data[Definition::AFTER]) && !is_array($data[Definition::AFTER])) {
            $data[Definition::AFTER] = array($data[Definition::AFTER]);
        }
        
        if (isset($data[Definition::LEVEL])) {
            $config = array_replace(
                $config,
                array(PluginConfig::PATCHER_LEVELS => array($data[Definition::LEVEL]))
            );
        }

        if (isset($data[Definition::PATCHER])) {
            $config[PluginConfig::PATCHER_SEQUENCE ][PluginConfig::PATCHER_APPLIERS] = array($data[Definition::PATCHER]);
        }
        
        return array(
            Definition::BEFORE => isset($data[Definition::BEFORE]) ? $data[Definition::BEFORE] : array(),
            Definition::AFTER => isset($data[Definition::AFTER]) ? $data[Definition::AFTER] : array(), 
            Definition::PATH => '',
            Definition::URL => $data[Definition::URL],
            Definition::SOURCE => $source,
            Definition::TARGETS => isset($data[Definition::TARGETS]) && $target === Definition::BUNDLE_TARGET
                ? $data[Definition::TARGETS]
                : array($target),
            Definition::SKIP => isset($data[Definition::SKIP])
                ? $data[Definition::SKIP]
                : false,
            Definition::LABEL => isset($data[Definition::LABEL])
                ? $data[Definition::LABEL]
                : $label,
            Definition::DEPENDS => $depends,
            Definition::CONFIG => $config
        );
    }
}
