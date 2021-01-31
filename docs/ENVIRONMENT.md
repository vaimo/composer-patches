# Commands

Installing the plugin will introduce a new composer command group: **patch**

```shell
# Apply new patches (similar to patch apply on 'composer install') 
composer patch:apply

# Re-apply all patches
composer patch:redo 

# Re-apply all patches and ignore any local repository changes on packages
composer patch:redo --force

# Re-apply patches for one speicif package
# (Keeps the patches that were not matched, applied)
composer patch:redo my/package 

# Re-apply all patches except patches declared against my/package
# (Keeps the patches that were not matched, applied)
composer patch:redo '!my/package'

# Re-apply patches for one specific package with patch name filter 
# (Keeps the patches that were not matched, applied)
composer patch:redo --filter wrong-time-format --filter other-file 

# Re-apply patches and skip filenames that contain 'some<anything>description'  
# (Keeps the patches that were not matched, applied)
composer patch:redo --filter '!some*description' my/package 

# Reset all patched packages
composer patch:undo 

# Reset one specific patched package
composer patch:undo my/package 

# Reset one specific patch on package
composer patch:undo --filter some-fix my/package

# Reset packages that have patch that contains 'some-fix' in it's path definition
composer patch:undo --filter some-fix

# Gather patches information from /vendor instead of install.json
composer patch:apply --from-source

# Ideal for testing out a newly added patch against my/package
composer patch:redo --from-source

# Check that all patches have valid target package info (using info from vendor)
composer patch:validate --from-source  

# Check that patches that are owner by ROOT package are all defined correctly
composer patch:validate --local

# List registered with their current status (with optional filter(s))
copmposer patch:list --status new --status removed

# List all patches that have either changed, are new or got removed 
composer patch:list --status '!applied'

```

The main purpose of this command is to make the maintenance of already created patches and adding new 
ones as easy as possible by allowing user to test out a patch directly right after defining it without 
having to trigger 'composer update' or 'composer install'.

## Environment Variables

* **COMPOSER_PATCHES_FATAL_FAIL** - exit after first patch failure is encountered (the default behaviour 
  for the patch applier is to continue gracefully).
* **COMPOSER_PATCHES_SKIP_PACKAGES** - comma-separated package names to exclude from patching, useful 
  when maintaining patches on package upgrade. Does not affect bundled patches.
* **COMPOSER_PATCHES_FROM_SOURCE** - always use data directly from owner's composer.json rather than 
  using the information stored in installed.json
* **COMPOSER_PATCHES_REAPPLY_ALL** - reapply all patches even when previously applied. Re-applies even 
  previously applied patches.
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