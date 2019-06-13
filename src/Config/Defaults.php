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
            Config::PATCHER_FILE => array(),
            Config::PATCHER_FILE_DEV => array(),
            Config::PATCHER_SEARCH => array(),
            Config::PATCHER_SEARCH_DEV => array(),
            Config::PATCHER_TARGETS => array(),
            Config::PATCHER_BASE_PATHS => array(),
            Config::PATCHER_SECURE_HTTP => true,
            Config::PATCHER_FORCE_RESET => false,
            Config::PATCHER_SOURCES => array(
                'project' => true,
                'packages' => true,
                'vendors' => true,
            ),
            Config::PATCHER_APPLIERS => array(
                'DEFAULT' => array(
                    'resolver' => array(
                        'default' => '< which',
                        'windows' => '< where'
                    )
                ),
                'GIT' => array(
                    'bin' => '[[resolver]] git',
                    'ping' => '!cd .. && [[bin]] rev-parse --is-inside-work-tree',
                    'check' => '[[bin]] apply -p{{level}} --check {{file}}',
                    'patch' => '[[bin]] apply -p{{level}} {{file}}'
                ),
                'PATCH' => array(
                    'bin' => '[[resolver]] patch',
                    'check' => '[[bin]] -t --verbose -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}',
                    'patch' => '[[bin]] -t -p{{level}} --no-backup-if-mismatch < {{file}}'
                )
            ),
            Config::PATCHER_OPERATIONS => array(
                'ping' => 'Usability test',
                'bin' => 'Availability test',
                'check' => 'Validation',
                'patch' => 'Patching'
            ),
            Config::PATCHER_FAILURES => array(
                'check' => array(
                    'garbage' => '/(\n|^)Hmm\.\.\.  Ignoring the trailing garbage/',
                    'reversals' => '/(\n|^)Reversed \(or previously applied\) patch detected/'
                )
            ),
            Config::PATCHER_SEQUENCE => array(
                Config::PATCHER_APPLIERS => array('PATCH', 'GIT'),
                Config::PATCHER_OPERATIONS => array('resolver', 'bin', 'ping', 'check', 'patch')
            ),
            Config::PATCHER_LEVELS => array('0', '1', '2')
        );
    }
}
