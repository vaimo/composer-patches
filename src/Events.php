<?php
namespace Vaimo\ComposerPatches;

class Events
{
    /**
     * The event listener method receives a Vaimo\ComposerPatches\PatchEvent instance.
     *
     * @var string
     */
    const PRE_APPLY = 'pre-patch-apply';

    /**
     * The event listener method receives a Vaimo\ComposerPatches\PatchEvent instance.
     *
     * @var string
     */
    const POST_APPLY = 'post-patch-apply';
}
