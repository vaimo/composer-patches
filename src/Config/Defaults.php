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
            Config::PATCHER_SECURE_HTTP => true,
            Config::PATCHER_SOURCES => array(
                'project' => true,
                'packages' => true,
                'vendors' => true,
            ),
            Config::PATCHER_APPLIERS => array(
                'GIT' => array(
                    'bin' => 'which git',
                    'ping' => '!cd .. && [[bin]] rev-parse --is-inside-work-tree',
                    'check' => '[[bin]] apply -p{{level}} --check {{file}}',
                    'patch' => '[[bin]] apply -p{{level}} {{file}}'
                ),
                'PATCH' => array(
                    'bin' => 'which patch',
                    'check' => '[[bin]] -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}',
                    'patch' => '[[bin]] -p{{level}} --no-backup-if-mismatch < {{file}}'
                )
            ),
            Config::PATCHER_OPERATIONS => array(
                'ping' => 'Usability test',
                'bin' => 'Availability test',
                'check' => 'Validation',
                'patch' => 'Patching'
            ),
            Config::PATCHER_SEQUENCE => array(
                Config::PATCHER_APPLIERS => array('PATCH', 'GIT'),
                Config::PATCHER_OPERATIONS => array('bin', 'ping', 'check', 'patch')
            ),
            Config::PATCHER_LEVELS => array('0', '1', '2')
        );
    }
}
