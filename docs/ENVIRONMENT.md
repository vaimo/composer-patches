# Environment Variables

* **COMPOSER_PATCHES_GRACEFUL** - continue applying patches even if some of them fail (default behaviour
  is to exit on first failure).
* **COMPOSER_PATCHES_SKIP_CLEANUP** - Will leave packages patched even when vaimo/composer-patches is 
  removed. By default, patched packages are re-installed to reset the patches (useful when creating 
  immutable build artifacts without any unnecessary modules installed).
* **COMPOSER_PATCHES_FORCE_RESET** - Allows patcher to reset patch-targeted packages that have local 
  changes. Default behaviour will lead to the process to be halted to avoid developers from losing their
  work.
* **COMPOSER_PATCHER_ALLOW_GLOBAL_USAGE** - Allows the plugin to be used while installed globally. The
  global usage is disabled by default due to potentially causing issues where project ends up working
  on developer's machine, but fails to build in CI server and run in production in similar manner
  as it was running on local machine (or just that some fixes don't end up in the server). 
