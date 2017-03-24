<?php
namespace Vaimo\ComposerPatches\Patch;

class Config
{
    /**
     * @var \Composer\Composer $composer
     */
    protected $composer;

    /**
     * @param \Composer\Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    /**
     * Enabled by default if there are project packages that include patches, but root package can still
     * explicitly disable them.
     *
     * @return bool
     */
    public function isPatchingEnabled()
    {
        $extra = $this->composer->getPackage()->getExtra();

        if (empty($extra['patches'])) {
            return isset($extra['enable-patching']) ? $extra['enable-patching'] : false;
        } else {
            return isset($extra['enable-patching']) && !$extra['enable-patching'] ? false : true;
        }
    }
}