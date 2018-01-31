<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Environment
{
    const FORCE_REAPPLY = 'COMPOSER_PATCHES_REAPPLY_ALL';
    const EXIT_ON_FAIL = 'COMPOSER_PATCHES_FATAL_FAIL';
    const PACKAGE_SKIP = 'COMPOSER_PATCHES_SKIP_PACKAGES';
    const PREFER_OWNER = 'COMPOSER_PATCHES_PREFER_OWNER';
    const SKIP_CLEANUP = 'COMPOSER_PATCHES_SKIP_CLEANUP';
}
