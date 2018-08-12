<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch;

class Definition
{
    const BUNDLE_TARGET = '*';

    const SOURCE = 'source';
    const TARGET = 'target';
    const PATH = 'path';
    const TMP = 'is_temporary';
    const URL = 'url';
    const LABEL = 'label';
    const DEPENDS = 'depends';
    const PACKAGE = 'package';
    const VERSION = 'version';
    const OWNER = 'owner';
    const ISSUE = 'issue';
    const LINK = 'link';
    const OWNER_IS_ROOT = 'owner_is_root';
    const HASH = 'md5';
    const TARGETS = 'targets';
    const SKIP = 'skip';

    const BEFORE = 'before';
    const AFTER = 'after';

    const CONFIG = 'config';
    const PATCHER = 'patcher';
    const LEVEL = 'level';

    const CHANGED = 'changed';
    const NEW = 'new';
}
