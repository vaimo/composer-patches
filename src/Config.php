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
    
    public function shouldResetEverything()
    {
        return (bool)getenv(Environment::FORCE_REAPPLY);
    }
    
    public function shouldExitOnFirstFailure()
    {
        return (bool)getenv(Environment::EXIT_ON_FAIL);
    }

    public function shouldPreferOwnerPackageConfig()
    {
        return (bool)getenv(Environment::PREFER_OWNER);
    }
    
    public function getSkippedPackages()
    {
        return array_filter(
            explode(',', getenv(Environment::PACKAGE_SKIP))
        );
    }
}
