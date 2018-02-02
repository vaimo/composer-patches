<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Config;

use Vaimo\ComposerPatches\Config;

class Defaults
{
    public function getPatcherConfig()
    {
        return array(
            Config::PATCHER_SOURCES => array(
                'project' => true,
                'packages' => true,
                'vendors' => true,
            ),
            Config::PATCHER_APPLIERS => array(
                'GIT' => array(
                    'check' => 'git apply -p{{level}} --check {{file}}',
                    'patch' => 'git apply -p{{level}} {{file}}'
                ),
                'PATCH' => array(
                    'check' => 'patch -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}',
                    'patch' => 'patch -p{{level}} --no-backup-if-mismatch < {{file}}'
                )
            ),
            Config::PATCHER_OPERATIONS => array(
                'check' => 'Validation',
                'patch' => 'Patching'
            ),
            Config::PATCHER_SEQUENCE => array(
                Config::PATCHER_APPLIERS => array('PATCH', 'GIT'),
                Config::PATCHER_OPERATIONS => array('check', 'patch')
            ),
            Config::PATCHER_LEVELS => array('0', '1', '2')
        );
    }
}
