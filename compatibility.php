<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace {
    // Backwards-compatibility for loophp/osinfo namespace change
    if (!class_exists('loophp\phposinfo\OsInfo') && class_exists('drupol\phposinfo\OsInfo')) {
        class_alias('drupol\phposinfo\OsInfo', 'loophp\phposinfo\OsInfo');
        class_alias('drupol\phposinfo\OsInfoInterface', 'loophp\phposinfo\OsInfoInterface');
        class_alias('drupol\phposinfo\Enum\Enum', 'loophp\phposinfo\Enum\Enum');
        class_alias('drupol\phposinfo\Enum\Family', 'loophp\phposinfo\Enum\Family');
        class_alias('drupol\phposinfo\Enum\FamilyName', 'loophp\phposinfo\Enum\FamilyName');
        class_alias('drupol\phposinfo\Enum\Os', 'loophp\phposinfo\Enum\Os');
        class_alias('drupol\phposinfo\Enum\OsName', 'loophp\phposinfo\Enum\OsName');
    }
}