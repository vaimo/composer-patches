<?php
namespace cweagans\Composer;

class PatchEvents
{
  /**
   * The event listener method receives a cweagans\Composer\PatchEvent instance.
   *
   * @var string
   */
  const PRE_PATCH_APPLY = 'pre-patch-apply';

  /**
   * The event listener method receives a cweagans\Composer\PatchEvent instance.
   *
   * @var string
   */
  const POST_PATCH_APPLY = 'post-patch-apply';
}
