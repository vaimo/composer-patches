<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

/**
 * NOTE: Backwards-compatibility for loophp/osinfo namespace change
 */
namespace {
    if (!class_exists('loophp\phposinfo\OsInfo') && class_exists('drupol\phposinfo\OsInfo')) {
        class_alias('loophp\phposinfo\OsInfo', 'drupol\phposinfo\OsInfo');
        class_alias('loophp\phposinfo\OsInfoInterface', 'drupol\phposinfo\OsInfoInterface');
        class_alias('loophp\phposinfo\Enum\Enum', 'drupol\phposinfo\Enum\Enum');
        class_alias('loophp\phposinfo\Enum\Family', 'drupol\phposinfo\Enum\Family');
        class_alias('loophp\phposinfo\Enum\FamilyName', 'drupol\phposinfo\Enum\FamilyName');
        class_alias('loophp\phposinfo\Enum\Os', 'drupol\phposinfo\Enum\Os');
        class_alias('loophp\phposinfo\Enum\OsName', 'drupol\phposinfo\Enum\OsName');
    }
}
