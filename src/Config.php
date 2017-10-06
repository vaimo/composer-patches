<?php
namespace Vaimo\ComposerPatches;

class Config
{
    const CONFIG_ROOT = 'extra';

    const LIST = 'patches';
    const DEV_LIST = 'patches-dev';

    const FILE = 'patches-file';
    const DEV_FILE = 'patches-file-dev';

    const EXCLUDED_PATCHES = 'excluded-patches';
    
    const APPLIED_FLAG = 'patches_applied';
}