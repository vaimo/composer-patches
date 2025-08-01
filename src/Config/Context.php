<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Config;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
if (!class_exists('loophp\phposinfo\OsInfo') && class_exists('drupol\phposinfo\OsInfo')) {
    class_alias('drupol\phposinfo\OsInfo', 'loophp\phposinfo\OsInfo');
    class_alias('drupol\phposinfo\OsInfoInterface', 'loophp\phposinfo\OsInfoInterface');
    class_alias('drupol\phposinfo\Enum\Enum', 'loophp\phposinfo\Enum\Enum');
    class_alias('drupol\phposinfo\Enum\Family', 'loophp\phposinfo\Enum\Family');
    class_alias('drupol\phposinfo\Enum\FamilyName', 'loophp\phposinfo\Enum\FamilyName');
    class_alias('drupol\phposinfo\Enum\Os', 'loophp\phposinfo\Enum\Os');
    class_alias('drupol\phposinfo\Enum\OsName', 'loophp\phposinfo\Enum\OsName');
}

use loophp\phposinfo\OsInfo;
use loophp\phposinfo\Enum\OsName;
use loophp\phposinfo\Enum\FamilyName;

class Context
{
    public function __construct()
    {
        OsInfo::register();
    }

    public function getOperationSystemTypeCode()
    {
        return preg_replace(
            '/-+/',
            '-',
            trim(preg_replace(
                '/[^A-Za-z0-9\-]/',
                '-',
                strtolower(OsInfo::os())
            ), '-')
        );
    }

    public function getOperationSystemName()
    {
        $typeId = OsInfo::os();

        $labels = array(
            OsName::DARWIN => 'mac',
            OsName::AIX => 'unix',
            OsName::GNU => 'unix',
            OsName::HPUX => 'unix',
            OsName::MINIX => 'unix',
            OsName::OSF1 => 'unix',
            OsName::QNX => 'unix',
            OsName::RELIANTUNIXY => 'unix',
            OsName::SCOSV => 'unix',
            OsName::SINIXY => 'unix',
            OsName::ULTRIX => 'unix',
            OsName::UNIXWARE => 'unix',
            OsName::UWIN => 'unix',
            OsName::UWINW7 => 'unix',
            OsName::ZOS => 'zos',
            OsName::DEBIANFREEBSD => 'bsd',
            OsName::FREEBSD => 'bsd',
            OsName::GNUFREEBSD => 'bsd',
            OsName::GNUKFREEBSD => 'bsd',
            OsName::NETBSD => 'bsd',
            OsName::OPENBSD => 'bsd',
            OsName::DRAGONFLY => 'bsd',
            OsName::GNULINUX => 'linux',
            OsName::LINUX => 'linux',
            OsName::WIN32 => 'windows',
            OsName::WINDOWS => 'windows',
            OsName::WINDOWSNT => 'windows',
            OsName::WINNT => 'windows',
            OsName::SOLARIS => 'solaris',
            OsName::SUNOS => 'sun',
            OsName::CYGWIN => 'cygwin',
            OsName::CYGWINNT51 => 'cygwin',
            OsName::CYGWINNT61 => 'cygwin',
            OsName::CYGWINNT61WOW64 => 'cygwin',
            OsName::MINGW => 'mingw',
            OsName::MINGW32NT61 => 'mingw',
            OsName::MSYSNT61 => 'mingw'
        );

        if (isset($labels[$typeId])) {
            return $labels[$typeId];
        }

        return '';
    }

    public function getOperationSystemFamily()
    {
        // @codingStandardsIgnoreStart
        $familyId = OsInfo::family(); // phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions
        // @codingStandardsIgnoreEnd

        $labels = array(
            FamilyName::BSD => 'unix',
            FamilyName::DARWIN => 'unix',
            FamilyName::LINUX => 'unix',
            FamilyName::WINDOWS => 'windows',
            FamilyName::UNIX_ON_WINDOWS => 'windows-unix'
        );

        if (isset($labels[$familyId])) {
            return $labels[$familyId];
        }

        return '';
    }
}
