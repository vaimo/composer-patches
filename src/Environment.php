<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Environment
{
    const GRACEFUL_MODE = 'COMPOSER_PATCHES_GRACEFUL';
    const SKIP_CLEANUP = 'COMPOSER_PATCHES_SKIP_CLEANUP';
    const FORCE_RESET = 'COMPOSER_PATCHES_FORCE_RESET';
}
