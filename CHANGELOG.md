# Changelog

_This file has been auto-generated from the contents of changelog.json_

## 3.48.3 (2019-08-19)

### Fix

* meta-tags overwriting each-other rather than stacking (when, say @depends is used multiple times)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.48.3) [diff](https://github.com/vaimo/composer-patches/compare/3.48.2...3.48.3)

## 3.48.2 (2019-08-12)

### Maintenance

* added notice to the 'most likely error' output (on patch failure) to indicate that the list presented is not the full list of details

Links: [src](https://github.com/vaimo/composer-patches/tree/3.48.2) [diff](https://github.com/vaimo/composer-patches/compare/3.48.1...3.48.2)

## 3.48.1 (2019-08-11)

### Fix

* broken compatibility with PHP 5.3 (wrong array syntax used)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.48.1) [diff](https://github.com/vaimo/composer-patches/compare/3.48.0...3.48.1)

## 3.48.0 (2019-08-11)

### Feature

* highlight most probable error that caused patch to fail in the non-verbose error message (full logs available when running with -vvv); this change was introduced to make it easier for people to identify issues within patches as the verbose output is extremely noisy [issues/34]

### Fix

* operation step always gets reported as 'UNKNOWN' when running the apply commands with heightened verbosity [issues/34]
* hard-coded path separators made patches to fail on certain operation systems (Windows) when applying remote patch [github/33]

### Maintenance

* exception and call stack directly exposed when executing code with 'halt of first failure'; changed to fail with proper error message (full stack still available with -vvv)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.48.0) [diff](https://github.com/vaimo/composer-patches/compare/3.47.2...3.48.0)

## 3.47.2 (2019-07-26)

### Fix

* support for older (<1.1) Composer releases faultily implemented where the availability with CommandsProvider was incorrectly checked for

Links: [src](https://github.com/vaimo/composer-patches/tree/3.47.2) [diff](https://github.com/vaimo/composer-patches/compare/3.47.1...3.47.2)

## 3.47.1 (2019-06-19)

### Fix

* wrong path used for pre-loading classes while running composer commands when the plugin package used as ROOT
* removed all syntax/code that was not compatible with 5.3
* removed/replaced all dependencies that were not compatible with 5.3
* having a patch for a back-ported fix for a package where when the package gets updated, the update could cause reverse-apply of the patch, thus reintroducing issue that was fixed in newer release (only happens with patches that should be declared with upper-capped version constraints but are not for some reason). This will cause patch failure from now on
* don't provide patch commands when plugin on older (<1.1) Composer versions [github/31]
* always load patches in alphabetical order from file-system when using patches-search (unless @after, @before directives used within the patch file; introduced in FileSystemUtils, using natural sorting) [github/29]
* fail the whole patching process with fatal exception when none of the patch applier commands (defined in the plugin config) are available [github/30]
* log output typos corrected

### Maintenance

* improved early autoloader setup within proxy plugin
* code normalised according to coding standards
* simple integration tests added that test the patch applying in sandbox Composer project

Links: [src](https://github.com/vaimo/composer-patches/tree/3.47.1) [diff](https://github.com/vaimo/composer-patches/compare/3.47.0...3.47.1)

## 3.47.0 (2019-04-09)

### Feature

* allow platform requirement dependencies on patches a'la php:>=7.2 (previously only package dependencies could be declared); usable with "depends" config or @depends tag

### Fix

* malformed package reset queue in some edge cases when using bundled patches which targets packages that have no direct patches applying on them
* load all the plugin classes on startup (to avoid crashes on patch apply); the old plugin logic will be used til the end of the particular Composer call that upgraded the plugin [github/28]

Links: [src](https://github.com/vaimo/composer-patches/tree/3.47.0) [diff](https://github.com/vaimo/composer-patches/compare/3.46.0...3.47.0)

## 3.46.0 (2019-04-03)

This release comes basically with re-written logic to the core of the patch apply queue generation due to issues with the old logic. The listing command now also uses same code which removes some of the confusion when using apply and seeing something different than what list reports

### Feature

* added --with-affected argument option for path:list command to list patches that indirectly are affected by the new/changed patches (would be re-applied on actually patch:apply due to package resets caused by new/changed statuses)
* patch owner embedded in applied patch registry to provide proper REMOVED information when patch gets removed


### Fix

* bundled patches partially reset when removing some dev-only (with --no-dev option) patches that targeted same packages as bundles did; Issue caused by only partially recursive lookup on an impact of re-installing certain composer package
* the alias argument for --explicit, --show-reapplies did not trigger explicit output
* make sure that repeated patch:undo calls don't reinstall previously undo'd patches
* make sure that patch:list uses same functionality that the main patch applier uses, thus guaranteeing that path:list will list the things as they'd be processed in actual patch apply run

Links: [src](https://github.com/vaimo/composer-patches/tree/3.46.0) [diff](https://github.com/vaimo/composer-patches/compare/3.45.0...3.46.0)

## 3.45.0 (2019-04-02)

### Feature

* allow patch:validate to use only patches that the root package owns: --local

### Fix

* patch contents not properly analysed when working with Bundled patches or using patches-search or patcher/search when trying to apply patches on Windows; Reason: using OS-specific EOL constant to split file content to lines [github/26]

Links: [src](https://github.com/vaimo/composer-patches/tree/3.45.0) [diff](https://github.com/vaimo/composer-patches/compare/3.44.0...3.45.0)

## 3.44.0 (2019-04-01)

### Feature

* Allow declaration of pathces that only apply when owner package is used as ROOT package (README/Patches: local patch)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.44.0) [diff](https://github.com/vaimo/composer-patches/compare/3.43.0...3.44.0)

## 3.43.0 (2019-03-31)

### Feature

* allow patch file paths, etc to be defined under extra/patcher key to make sure that they don't hog up too much main level keys of 'extra' config for given package (old keys are also still supported)
* allow some patches to be ignored when running patch:validate by providing list of path ignores in package's configuration: extra/patcher/ignore (takes array of ignored paths)
* allow patcher operations to be split to have separate sub-operation per OS type [github/26]

### Fix

* some path processing functions did not use proper directory separator constant
* patches not applied properly on Windows due to using 'which' instead of 'where' when resolving the patch applier absolute path [gitdhub/26]

Links: [src](https://github.com/vaimo/composer-patches/tree/3.43.0) [diff](https://github.com/vaimo/composer-patches/compare/3.42.2...3.43.0)

## 3.42.2 (2019-03-21)

### Fix

* additional rollbacks on newer array declaration usage (broke compatibility with 5.3)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.42.2) [diff](https://github.com/vaimo/composer-patches/compare/3.42.1...3.42.2)

## 3.42.1 (2019-03-21)

### Fix

* rollback on newer array declaration usage (broke compatibility with 5.3)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.42.1) [diff](https://github.com/vaimo/composer-patches/compare/3.42.0...3.42.1)

## 3.42.0 (2019-03-21)

### Feature

* added more informative patches configuration JSON validation (that gives exact details on what's wrong with the JSON file)
* added alias for --excluded argument: --with-excludes for more intuitive usage and to move towards more self-documenting arguments
* added alias for --explicit argument: --show-reapplies for more intuitive usage and to move towards more self-documenting arguments
* added reason to patch:validation errors (either 'NO FILE' or 'NO CONFIG')

### Fix

* incorrect TMP path on Windows [github/22]
* crash when running patch:list in situation where none of bundled patches dependencies is directly available (expectation: should be able to list all patches even when none of the targets are installed)
* the patch:validate not catching situations where there's a patch JSON declaration that has no corresponding file at the place where the declaration targets
* better error message when wanting to download patches from unsecure URLs by referring to documentation of the plugin rather than to documentation of Composer (the module has it's own 'secure-http' config option that only affects patches)
* patch validation not properly issues that one can have with remote patches (report UNSECURE and ERROR 404 patches)

### Maintenance

* made patch commands available within the plugin itself (not currently used for anything, potentially used in integration tests in the near future)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.42.0) [diff](https://github.com/vaimo/composer-patches/compare/3.41.0...3.42.0)

## 3.41.0 (2019-02-20)

### Feature

* allow patcher config overrides per package instead of allowing it only per patch definition (reserved key: '_config'). More on this under the topic of 'Patches: shared config' in the README

Links: [src](https://github.com/vaimo/composer-patches/tree/3.41.0) [diff](https://github.com/vaimo/composer-patches/compare/3.40.0...3.41.0)

## 3.40.0 (2019-02-12)

### Feature

* allow patch to target the autoloader root (instead of targeting package root) by configuring 'cwd' key to 'autoload'

Links: [src](https://github.com/vaimo/composer-patches/tree/3.40.0) [diff](https://github.com/vaimo/composer-patches/compare/3.39.2...3.40.0)

## 3.39.2 (2019-02-12)

### Maintenance

* follow-up to incorrectly released version (3.39.1); no extra changes done

Links: [src](https://github.com/vaimo/composer-patches/tree/3.39.2) [diff](https://github.com/vaimo/composer-patches/compare/3.39.1...3.39.2)

## 3.39.1 (2019-02-12)

### Fix

* patches not applied when dealing with requires that use dev branches (even when version available in package config or when dev branch aliased as proper version)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.39.1) [diff](https://github.com/vaimo/composer-patches/compare/3.39.0...3.39.1)

## 3.39.0 (2019-02-12)

### Feature

* added --brief to list command (skips over description, etc)
* when using --filter or targeting a specific package with 'redo', 'undo' or 'apply', show only those patches that match with the filter; other patches still applied, just not reported (all actions can still be made visible when using --explicit flag)
* show information about patches that are removed (when patches are re-applied, show which items were applied, but no longer listed/queued)

### Fix

* rework on fix done in 3.37.1 for patches that applied half-way while 'patch' command still returned SUCCESS exit code; perform patch command dry-apply output analysis to detect edge-cases that should be considered as failures
* the command 'list' listing removals even when --filter used
* the command 'list' --excluded option not properly dealing with filter when --filter used
* the command 'list' not listing removals when every patch against some package gets removed
* the command 'list' always listing removed items, even when they do not match with used filter
* allow both 'undo' and 'redo' command to finish even when there are errors encountered while re-applying some patches (bundled patches might get re-applied; encountered when doing partial/targeted command call); suppress environment flags that tell otherwise (apply still stops on first failure when required)
* queue resolver re-introducing undo'd patches when patches targeting a package that had bundled patches defined against it
* show all applied patches when running 'redo' command without a filter (otherwise only matched patches are shown)
* hide NEW patches (that might be failing) when doing filtered redo/undo calls to avoid too much noise in output where it's pretty clear that developer is working with a sub-selection of patches (all shown when doing explicit call)
* composer lock still modified when applying patches (leaves empty EXTRA key behind where there previously wasn't one present)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.39.0) [diff](https://github.com/vaimo/composer-patches/compare/3.38.0...3.39.0)

## 3.38.0 (2018-09-27)

### Feature

* hide all information on previously applied patches by default (when patches for certain package are re-applied) and only show the ones that changed, are new or got matches with a filter (--explicit flag added for commands to use old output that shows every patch that gets re-applied)

### Fix

* rollback to fix in 3.37.1 (caused errors for patches that created new files, new solution needed)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.38.0) [diff](https://github.com/vaimo/composer-patches/compare/3.37.1...3.38.0)

## 3.37.1 (2018-09-26)

### Fix

* some patches that patched multiple files applied only half-way through on certain OS's without returning with proper exit code on failure (or failed hunks considered as ignoreable junk)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.37.1) [diff](https://github.com/vaimo/composer-patches/compare/3.37.0...3.37.1)

## 3.37.0 (2018-09-18)

### Feature

* new flag introduced for patch:list to allow including patches that have been ruled out due to mismatch with certain constraint: --excluded

### Fix

* embedded targeting info for bundled patches that declares constrains not processed correctly (@depends,@version)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.37.0) [diff](https://github.com/vaimo/composer-patches/compare/3.36.0...3.37.0)

## 3.36.0 (2018-09-18)

### Feature

* allow '@type bundle' to be used for bundle package instead of using somewhat cryptic '@package *'
* allow multiple types to be combined in embedded info (@type dev+bundle)

### Fix

* multi-line description not presented properly when using composer patch:list command
* bundled patches not included as expected when using embedded patch target info

Links: [src](https://github.com/vaimo/composer-patches/tree/3.36.0) [diff](https://github.com/vaimo/composer-patches/compare/3.35.3...3.36.0)

## 3.35.3 (2018-09-14)

### Fix

* backwards compatibility problems with composer.lock contents for projects that upgraded to latest releases of the plugin and had patches previously applied (boolean values for patches_applied no longer tolerated)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.35.3) [diff](https://github.com/vaimo/composer-patches/compare/3.35.2...3.35.3)

## 3.35.2 (2018-09-11)

### Fix

* lock management reworked (again) to make sure that under no circumstances does the plugin crash while sanitizing the lock

Links: [src](https://github.com/vaimo/composer-patches/tree/3.35.2) [diff](https://github.com/vaimo/composer-patches/compare/3.35.1...3.35.2)

## 3.35.1 (2018-09-10)

### Fix

* lock management reworked to use built-in methods for locating a package to avoid issues with potential aliases, etc

Links: [src](https://github.com/vaimo/composer-patches/tree/3.35.1) [diff](https://github.com/vaimo/composer-patches/compare/3.35.0...3.35.1)

## 3.35.0 (2018-09-10)

### Feature

* list command added (allows listing all registered patches and their state)

### Fix

* bundled patch targets not properly reset when using patch:redo in case patches applied on packages, but installed.json reset to provide no information about applied patches
* patches for packages that are covered with certain bundle patch do not get re-applied when running patch:redo with filter (redo should not leave any patch uninstalled even when executed with a filter)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.35.0) [diff](https://github.com/vaimo/composer-patches/compare/3.34.1...3.35.0)

## 3.34.1 (2018-09-02)

### Fix

* conflicting project applied patches state when doing a --no-dev call and a filtered undo/redo after that

Links: [src](https://github.com/vaimo/composer-patches/tree/3.34.1) [diff](https://github.com/vaimo/composer-patches/compare/3.34.0...3.34.1)

## 3.34.0 (2018-09-01)

### Feature

* allow patch path strip level to be defined in patch's embedded target-info declaration (@level <int>)

### Fix

* patches queue generator sometimes generated lists that did not queue proper re-applying of patches when dealing with bundles and indirect targets so that 'patch:redo' == 'patch:undo' + 'patch:apply'
* make sure that the removal od 'dev' bundle (or any other type with indirect targets) patches cause proper re-patching of related targets when running with --no-dev

Links: [src](https://github.com/vaimo/composer-patches/tree/3.34.0) [diff](https://github.com/vaimo/composer-patches/compare/3.33.1...3.34.0)

## 3.33.1 (2018-08-30)

### Fix

* improved error reporting when encountering issues on composer lock cleanup

Links: [src](https://github.com/vaimo/composer-patches/tree/3.33.1) [diff](https://github.com/vaimo/composer-patches/compare/3.33.0...3.33.1)

## 3.33.0 (2018-08-22)

### Feature

* allow patch applied root to be configured per patch to allow patching of files that are mapped by other composer plugins to project root (see more at 'Patches: custom applier cwd)'

### Fix

* switched away from using a class constant name that on older php versions is reserved (new)
* avoid reporting non-vcs package differences as reason for halting the patch applying (which requires package reset)
* full list of applied patches ended up in composer.lock (new: none added, not even 'true' flag)
* multiple dependencies on patches with embedded target info not respected

Links: [src](https://github.com/vaimo/composer-patches/tree/3.33.0) [diff](https://github.com/vaimo/composer-patches/compare/3.32.1...3.33.0)

## 3.32.1 (2018-08-16)

### Fix

* definition list validation command returning with false failures when using #skip on patch paths

### Maintenance

* removing #skip flag from path when encountered (previously it remained in the path)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.32.1) [diff](https://github.com/vaimo/composer-patches/compare/3.32.0...3.32.1)

## 3.32.0 (2018-08-16)

### Feature

* allow comments on any level and with any keyword as long as it starts with underscore (_)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.32.0) [diff](https://github.com/vaimo/composer-patches/compare/3.31.0...3.32.0)

## 3.31.0 (2018-08-15)

### Feature

* halt applying patches when encountering package with local changes to avoid developer from losing their work (allow override with env flag or command --force flag)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.31.0) [diff](https://github.com/vaimo/composer-patches/compare/3.30.1...3.31.0)

## 3.30.1 (2018-08-15)

### Fix

* patch apply failure on fresh projects under certain conditions when loading the 'patches-applied' from .lock file (which in-between the run is set to 'true')
* skip initiating the patches in case composer update is only targeting composer.lock (running update with --lock) and not really installing/updating any modules

Links: [src](https://github.com/vaimo/composer-patches/tree/3.30.1) [diff](https://github.com/vaimo/composer-patches/compare/3.30.0...3.30.1)

## 3.30.0 (2018-08-14)

### Feature

* allow @type tag in embedded target info to indicate that patch should only be applied when running composer without --no-dev

### Fix

* patch:redo not re-applying patches correctly for targeted package when empty patch list provided (in situations where previously patches were applied)
* when testing bundled packages with --filter, consider all bundle patch targets as full reset candidates (used to produce inconsistent behaviour)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.30.0) [diff](https://github.com/vaimo/composer-patches/compare/3.29.1...3.30.0)

## 3.29.1 (2018-08-12)

### Fix

* patch validation command failing on bundle patches when owned by non-root package

Links: [src](https://github.com/vaimo/composer-patches/tree/3.29.1) [diff](https://github.com/vaimo/composer-patches/compare/3.29.0...3.29.1)

## 3.29.0 (2018-08-12)

### Feature

* split the --undo and --redo options of main command into separate commands - 'patch:undo' and 'patch:redo' (the functionality will still also be accessible via flags of 'composer patch')
* create alias command for 'composer patch' to have all commands available under the keyword 'patch' - patch:apply ('composer patch' still works as well though)
* added 'patch:validate' to make sure that there are no patches in owner repo that miss proper target definition (has been a real issue when re-arranging code in some projects)
* allow one-line embedded definition via @package tag the same way it's possible with @version
* added aliases for @package - @target, @module
* added alias for @version - @constraint

### Fix

* ticket info never displayed for patch with embedded info, even when @ticket set

### Maintenance

* change towards not passing composer instance into functions and using it as dependency instead
* make it possible to manipulate composer loader work by toggling or replacing loader components

Links: [src](https://github.com/vaimo/composer-patches/tree/3.29.0) [diff](https://github.com/vaimo/composer-patches/compare/3.28.1...3.29.0)

## 3.28.1 (2018-08-10)

### Fix

* patch link/issue moved to be presented after the label/description

Links: [src](https://github.com/vaimo/composer-patches/tree/3.28.1) [diff](https://github.com/vaimo/composer-patches/compare/3.28.0...3.28.1)

## 3.28.0 (2018-08-10)

### Feature

* allow patches information to be extracted from the patch file (see: patch declaration with embedded meta-data)
* allow comments to be added to patch declaration files by using '_comment' prefix on main level of declaration
* show more detailed reason for re-applying a patch: NEW, when new; CHANGED, when file is not new, but contents changed

### Fix

* using patches files on project level or for root repository did not resolve their paths correctly
* undo having confusing semantic meaning when used. targeting a specific package now always just targets the package, path filter gets negated on undo command
* work towards restoring compatibility with 5.3

Links: [src](https://github.com/vaimo/composer-patches/tree/3.28.0) [diff](https://github.com/vaimo/composer-patches/compare/3.27.0...3.28.0)

## 3.27.0 (2018-05-18)

### Feature

* allow multiple 'patches-depend' declarations for different kind of patches (bundle VS normal patch)
* allow wildcards to be used in target names when defining multiple patches-depends items

Links: [src](https://github.com/vaimo/composer-patches/tree/3.27.0) [diff](https://github.com/vaimo/composer-patches/compare/3.26.0...3.27.0)

## 3.26.0 (2018-05-08)

### Feature

* allow multiple patch-base's to be defined based on vendor or full package name

Links: [src](https://github.com/vaimo/composer-patches/tree/3.26.0) [diff](https://github.com/vaimo/composer-patches/compare/3.25.3...3.26.0)

## 3.25.3 (2018-03-07)

### Fix

* missing file added

Links: [src](https://github.com/vaimo/composer-patches/tree/3.25.3) [diff](https://github.com/vaimo/composer-patches/compare/3.25.2...3.25.3)

## 3.25.2 (2018-03-06)

### Fix

* array-based values ended up being treated as version constraints (issue with label-version exploder)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.25.2) [diff](https://github.com/vaimo/composer-patches/compare/3.25.1...3.25.2)

## 3.25.1 (2018-03-06)

### Fix

* label-version definition exploder not handling a list of multiple versions correctly

Links: [src](https://github.com/vaimo/composer-patches/tree/3.25.1) [diff](https://github.com/vaimo/composer-patches/compare/3.25.0...3.25.1)

## 3.25.0 (2018-03-06)

### Feature

* new definition format that consists of description/filename and version restriction
* allow label to be used as file-name (defined in base-path pattern as {{label}} or {{label-value}})
* allow items without mutations (like {{label}}) to have strip-rules defined against them
* don't display label when file name is directly part of the label

### Fix

* bad data type returned when base-path pattern defined and certain parts of the name did not match with any value-strip rule
* skip flag not correctly moved to the end of the path when using patches-base configuration
* don't append the base-path pattern with custom extras and expect users to take charge of making sure that the file name is part of the path
* trim whitespace, slash, colon when patch source starts with space

Links: [src](https://github.com/vaimo/composer-patches/tree/3.25.0) [diff](https://github.com/vaimo/composer-patches/compare/3.24.1...3.25.0)

## 3.24.1 (2018-02-28)

### Maintenance

* debug code removed (shame!)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.24.1) [diff](https://github.com/vaimo/composer-patches/compare/3.24.0...3.24.1)

## 3.24.0 (2018-02-28)

### Feature

* allow patch base path variables to have partial value extractors defined against them
* use 0.0.0 version variable value when base-path definition includes version in it
* include new base path variable: {{file-name}} (name derived from label) and {{file}} (name used from source as is)
* allow file name to be resolved from patch label (when source not defined)

### Fix

* wrong variable name used at patcher config normalizer component that caused a crash when 'config' used for patch definition

### Maintenance

* composer lock removed; readme mic statistics badges added

Links: [src](https://github.com/vaimo/composer-patches/tree/3.24.0) [diff](https://github.com/vaimo/composer-patches/compare/3.23.5...3.24.0)

## 3.23.5 (2018-02-13)

### Fix

* wrong error message when bundle patch is not found from defined path

Links: [src](https://github.com/vaimo/composer-patches/tree/3.23.5) [diff](https://github.com/vaimo/composer-patches/compare/3.23.4...3.23.5)

## 3.23.4 (2018-02-13)

### Maintenance

* minor modifications to patch filtering logic

Links: [src](https://github.com/vaimo/composer-patches/tree/3.23.4) [diff](https://github.com/vaimo/composer-patches/compare/3.23.3...3.23.4)

## 3.23.3 (2018-02-13)

### Fix

* fix to a crash that happened when resetting targets that relate to bundle patches

### Maintenance

* definition normalizer split into separate components
* patch applier partially split into smaller components

Links: [src](https://github.com/vaimo/composer-patches/tree/3.23.3) [diff](https://github.com/vaimo/composer-patches/compare/3.23.2...3.23.3)

## 3.23.2 (2018-02-12)

### Fix

* minor change to constant naming to make it match up with patch command. COMPOSER_PATCHES_PREFER_OWNER renamed to COMPOSER_PATCHES_FROM_SOURCE. Backwards compatible
* re-installed packages (when resetting patches) not being re-deployed when using 'patch' command (resulting in incorrect app behaviour due to some old files being still used)

### Maintenance

* log messages updated to include applied patch count when resetting package
* showing [NEW] flag when re-applying patches for a package and patch file was previously not applied

Links: [src](https://github.com/vaimo/composer-patches/tree/3.23.2) [diff](https://github.com/vaimo/composer-patches/compare/3.23.1...3.23.2)

## 3.23.1 (2018-02-12)

### Fix

* bundled patches reset mechanism targeting wrong packages (where root package as owner was being sent to 'patches reset' which caused a crash)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.23.1) [diff](https://github.com/vaimo/composer-patches/compare/3.23.0...3.23.1)

## 3.23.0 (2018-02-12)

### Feature

* allow default indirect dependency to be defined for a package version restriction
* allow base patch to be defined for widgets - including variable conversion for vendor, module name and version
* introduced new definition pattern to be used together with patches-base and base patch variables: version ranges

### Fix

* renamed constants that conflicted with constant names that were reserved in PHP5.3.X

Links: [src](https://github.com/vaimo/composer-patches/tree/3.23.0) [diff](https://github.com/vaimo/composer-patches/compare/3.22.4...3.23.0)

## 3.22.4 (2018-02-08)

### Maintenance

* package meta-data fixes. syntax error in composer.json

Links: [src](https://github.com/vaimo/composer-patches/tree/3.22.4) [diff](https://github.com/vaimo/composer-patches/compare/3.22.3...3.22.4)

## 3.22.3 (2018-02-08)

### Fix

* incorrect re-applying of bundle patch after 'patch' command had been used to exclude it or when single bundle patch was explicitly targeted
* package not reset when using --filter (without --redo), even when targeted patches were a subset of what was applied
* the --filter flag not properly respected when running 'patch' command with --undo

Links: [src](https://github.com/vaimo/composer-patches/tree/3.22.3) [diff](https://github.com/vaimo/composer-patches/compare/3.22.2...3.22.3)

## 3.22.2 (2018-02-08)

### Fix

* bundled patch targets resolver failing due to patch info loaders being re-arranged and certain array values no longer being available (patch path not available as array keys)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.22.2) [diff](https://github.com/vaimo/composer-patches/compare/3.22.1...3.22.2)

## 3.22.1 (2018-02-08)

### Fix

* minor architecture changes around filtering patch files / packages

Links: [src](https://github.com/vaimo/composer-patches/tree/3.22.1) [diff](https://github.com/vaimo/composer-patches/compare/3.22.0...3.22.1)

## 3.22.0 (2018-02-08)

### Feature

* allow patch exclusions based on partial paths
* renamed 'excluded-patches' to 'patches-exclude' to follow similar naming convention through-out the configuration for the plugin. Backwards compatible

### Fix

* patch exclusion failed to kick in due to bad configuration pass-down from factory to the component that is responsible for the exclusion

Links: [src](https://github.com/vaimo/composer-patches/tree/3.22.0) [diff](https://github.com/vaimo/composer-patches/compare/3.21.0...3.22.0)

## 3.21.0 (2018-02-08)

### Feature

* patcher configuration overrides that depend on the OS
* topological sorting on patches to allow sequencing even when patches not defined by same owner or defined in different patch-list files
* allow fuzzy package name targeting with 'patch' command

### Fix

* the --filter argument to work similarly to how package filter narrows down on what is being targeted

Links: [src](https://github.com/vaimo/composer-patches/tree/3.21.0) [diff](https://github.com/vaimo/composer-patches/compare/3.20.0...3.21.0)

## 3.20.0 (2018-02-05)

### Feature

* make it possible to allow patch download from HTTP (separate config from allowing this for packages)

### Fix

* fail early when some of the remote patches fail to download (only for patches that are actually required)
* make sure that same patch file is not downloaded twice (might happen with bundled patches)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.20.0) [diff](https://github.com/vaimo/composer-patches/compare/3.19.5...3.20.0)

## 3.19.5 (2018-02-04)

### Fix

* re-apply remote patch when it's contents have changed (download remote patches beforehand and make the decision based on the contents of the patch)
* remote patches treated as if they're local patch files

### Maintenance

* moved patch downloading to be done before any patches are applied
* documentation simplified. Using comments in examples to explain what certain config does

Links: [src](https://github.com/vaimo/composer-patches/tree/3.19.5) [diff](https://github.com/vaimo/composer-patches/compare/3.19.4...3.19.5)

## 3.19.4 (2018-02-03)

### Maintenance

* documentation re-organized and simplified

Links: [src](https://github.com/vaimo/composer-patches/tree/3.19.4) [diff](https://github.com/vaimo/composer-patches/compare/3.19.3...3.19.4)

## 3.19.3 (2018-02-03)

### Maintenance

* minor readme and package description updates

Links: [src](https://github.com/vaimo/composer-patches/tree/3.19.3) [diff](https://github.com/vaimo/composer-patches/compare/3.19.2...3.19.3)

## 3.19.2 (2018-02-03)

### Maintenance

* documentation changes. Some explanations re-written. Added example for bundle-patch

Links: [src](https://github.com/vaimo/composer-patches/tree/3.19.2) [diff](https://github.com/vaimo/composer-patches/compare/3.19.1...3.19.2)

## 3.19.1 (2018-02-03)

### Maintenance

* documentation re-organized to prioritize quick comprehension on the basics of the module's functionality
* minor code restyle changes

Links: [src](https://github.com/vaimo/composer-patches/tree/3.19.1) [diff](https://github.com/vaimo/composer-patches/compare/3.19.0...3.19.1)

## 3.19.0 (2018-02-02)

### Feature

* added new operation to check if applier is available (can be used to exclude certain appliers on certain systems)
* added new operation to find applier's executable and use it in the operations that come afterwards
* allow multiple commands per operation
* introduced the possibility to define an operation that is considered a success when the command does not succeed

### Fix

* removed references to $this within closures (as it's not supported in 5.3.X)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.19.0) [diff](https://github.com/vaimo/composer-patches/compare/3.18.0...3.19.0)

## 3.18.0 (2018-02-02)

### Feature

* several config keys renamed (patchers => appliers, patcher-config => patcher). Backwards compatible
* patch enabling moved under patcher/sources (project:bool, packages:bool|array, vendors:bool|array). Backwards compatible
* allow granular patch sources inclusion (so that only some vendors would be included)
* allow some providers to have special extra operations (before this change, every applier was expected to have every listed operation declared)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.18.0) [diff](https://github.com/vaimo/composer-patches/compare/3.17.3...3.18.0)

## 3.17.3 (2018-02-01)

### Fix

* switched to using PHP constants for OS-related values like newline and path separator

### Maintenance

* switched to using constants for each free-text array key value + reduced code repetition
* logger indentation reworked not to be hardcoded in log messages in text form to open up the chance to switch to different logging methods/formats in the future

Links: [src](https://github.com/vaimo/composer-patches/tree/3.17.3) [diff](https://github.com/vaimo/composer-patches/compare/3.17.2...3.17.3)

## 3.17.2 (2018-02-01)

### Fix

* patches not registered for patch target packages when 'COMPOSER_PATCHES_FATAL_FAIL' enabled and error is encountered

Links: [src](https://github.com/vaimo/composer-patches/tree/3.17.2) [diff](https://github.com/vaimo/composer-patches/compare/3.17.1...3.17.2)

## 3.17.1 (2018-02-01)

### Fix

* composer patch command option 'undo' not working correctly when patching explicitly set to be enabled in composer.json of the project
* composer patch command option 'redo' not working correctly when patching explicitly set to be enabled in composer.json of the project
* using 'undo' and 'redo' together still triggers 'undo' functionality

Links: [src](https://github.com/vaimo/composer-patches/tree/3.17.1) [diff](https://github.com/vaimo/composer-patches/compare/3.17.0...3.17.1)

## 3.17.0 (2018-01-31)

### Fix

* don't force bundle patches to have 'vendor' in their paths as it's a customizable value
* patch information not correctly restored to installed.json when there were no patch updates while running 'composer update'

### Maintenance

* code split into smaller classes where applicable to move towards single-responsibility entities in design
* reduced the mess around re-using same terminology in too many different contexts

Links: [src](https://github.com/vaimo/composer-patches/tree/3.17.0) [diff](https://github.com/vaimo/composer-patches/compare/3.16.0...3.17.0)

## 3.16.0 (2018-01-31)

### Feature

* auto-resolve bundle patch targets when 'targets' not defined
* multiple filters for patch command
* allow patch command path filter to have wildcards and negation

### Fix

* ignore custom 'targets' config for non-bundled patches
* bundled patch was not registering/resetting target packages when performing redo/undo
* patches-dev and patches-file not enabling patching by default when defined on project level
* having patcher enabled only on project level did not compile patch queue correctly when disabling the option of including patches from packages

Links: [src](https://github.com/vaimo/composer-patches/tree/3.16.0) [diff](https://github.com/vaimo/composer-patches/compare/3.15.0...3.16.0)

## 3.15.0 (2018-01-29)

### Feature

* allow multiple patch files to be defined to enable high-level patch grouping (or to allow occasional cleanup where really old legacy patches could be moved elsewhere)

### Fix

* patch files not loaded from relative path even when they belong to a package rather than being referred directly from the project

Links: [src](https://github.com/vaimo/composer-patches/tree/3.15.0) [diff](https://github.com/vaimo/composer-patches/compare/3.14.1...3.15.0)

## 3.14.1 (2018-01-29)

### Fix

* crash when trying to declare path stripping level for version-branched sources

Links: [src](https://github.com/vaimo/composer-patches/tree/3.14.1) [diff](https://github.com/vaimo/composer-patches/compare/3.14.0...3.14.1)

## 3.14.0 (2018-01-28)

### Feature

* allow certain patches to be processed only with very strict path strip options and patcher type
* changed patcher definition template to use variable markup rather than relying on sprintf patterns which dictates the variables in the template to be defined in certain order
* allow extra operations to be defined or the sequence of existing ones to be changed

### Fix

* made sure that no compact array markup is used within the plugin

### Maintenance

* changed the 'validate' in patcher configuration key to 'check'. Support for 'validate' kept

Links: [src](https://github.com/vaimo/composer-patches/tree/3.14.0) [diff](https://github.com/vaimo/composer-patches/compare/3.13.2...3.14.0)

## 3.13.2 (2018-01-26)

### Fix

* updated lock to latest due to composer validate error

Links: [src](https://github.com/vaimo/composer-patches/tree/3.13.2) [diff](https://github.com/vaimo/composer-patches/compare/3.13.1...3.13.2)

## 3.13.1 (2018-01-26)

### Fix

* roll-back with 'undo' to reset package when used with specific targets

Links: [src](https://github.com/vaimo/composer-patches/tree/3.13.1) [diff](https://github.com/vaimo/composer-patches/compare/3.13.0...3.13.1)

## 3.13.0 (2018-01-26)

### Feature

* option to apply only some of the patches based on text-based file name filter
* added an option for the user to have control over the sequence of the patchers

### Fix

* patch path strip levels re-ordered to go sequentially from 0 to 2 to allow first run to be with 'as is' path
* changed patch applier logic to test different patchers with same level rather than going through all patches with levels in sequence
* preferring standard patcher instead of starting with GIT
* patches not being reset when removing all patches from patch provider in vendor folder and running '--from-source --redo my/package'

Links: [src](https://github.com/vaimo/composer-patches/tree/3.13.0) [diff](https://github.com/vaimo/composer-patches/compare/3.12.1...3.13.0)

## 3.12.1

### Feature

* renamed 'reset' to 'redo' to make the command argument's purpose easier to understand when compared with 'redo'

### Fix

* properly re-apply all patches when using 'from-source' nad 'redo' arguments together

Links: [src](https://github.com/vaimo/composer-patches/tree/3.12.1) [diff](https://github.com/vaimo/composer-patches/compare/3.12.0...3.12.1)

## 3.12.0 (2017-12-21)

### Feature

* introduced a new composer command to make it easier to re-apply all patches and give newly defined patches a quick test-run (composer patch)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.12.0) [diff](https://github.com/vaimo/composer-patches/compare/3.11.0...3.12.0)

## 3.11.0 (2017-12-21)

### Feature

* reset all patched packages when vaimo/composer-patches in removed from a project (with an option of leaving the patches applied)
* added the possibility for a project to define custom patch appliers or override the ones that are built into the package (see: Patcher Configuration)

### Fix

* avoid crashing at the end of a composer operation when vaimo/composer-patches was removed while it was executing, but it's plugin class remains loaded and triggers an action after all install/uninstall actions are done

Links: [src](https://github.com/vaimo/composer-patches/tree/3.11.0) [diff](https://github.com/vaimo/composer-patches/compare/3.10.4...3.11.0)

## 3.10.4 (2017-12-18)

### Fix

* changes to package meta-data

Links: [src](https://github.com/vaimo/composer-patches/tree/3.10.4) [diff](https://github.com/vaimo/composer-patches/compare/3.10.3...3.10.4)

## 3.10.3 (2017-12-18)

### Fix

* crash due to 'missing array key' that's caused by bad comparison in code when using only 'depends' on certain patch declarations

Links: [src](https://github.com/vaimo/composer-patches/tree/3.10.3) [diff](https://github.com/vaimo/composer-patches/compare/3.10.2...3.10.3)

## 3.10.2 (2017-10-09)

### Fix

* declaration of 'depends' was overriding 'version'. Constraints defined on those keys are now merged
* patch applied when single version constraint was matched even when multiple ones defined

Links: [src](https://github.com/vaimo/composer-patches/tree/3.10.2) [diff](https://github.com/vaimo/composer-patches/compare/3.10.1...3.10.2)

## 3.10.1 (2017-10-08)

### Maintenance

* changes to package metadata

Links: [src](https://github.com/vaimo/composer-patches/tree/3.10.1) [diff](https://github.com/vaimo/composer-patches/compare/3.10.0...3.10.1)

## 3.10.0 (2017-10-08)

### Feature

* environment variable names standardized (old names still supported)

### Fix

* patches not re-applied when package is upgraded (old 'applied_patches' incorrectly restored instead)
* root package ignored when using COMPOSER_PATCHES_PREFER_OWNER

### Maintenance

* code re-organized to centralize the access to env flags

Links: [src](https://github.com/vaimo/composer-patches/tree/3.10.0) [diff](https://github.com/vaimo/composer-patches/compare/3.9.0...3.10.0)

## 3.9.0 (2017-10-06)

### Feature

* added new environment flag to force patcher to extract the patch info from vendor folder instead of using the information from installed.json (mainly for patch maintenance)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.9.0) [diff](https://github.com/vaimo/composer-patches/compare/3.8.1...3.9.0)

## 3.8.1 (2017-10-06)

### Fix

* process every source path and check for 'skip' flag. In certain cases, the source-path flag was ignored

Links: [src](https://github.com/vaimo/composer-patches/tree/3.8.1) [diff](https://github.com/vaimo/composer-patches/compare/3.8.0...3.8.1)

## 3.8.0 (2017-10-06)

### Feature

* allow patches to be skipped by adding 'skip' flag in it's definition (good as maintenance flags when doing major base-framework upgrades)

### Fix

* excluded patches required develop to specify patch owner vendor path instead of just the path that was relative to the patch owner folder

Links: [src](https://github.com/vaimo/composer-patches/tree/3.8.0) [diff](https://github.com/vaimo/composer-patches/compare/3.7.1...3.8.0)

## 3.7.1 (2017-09-23)

### Maintenance

* code cleanup (some debugging code removed)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.7.1) [diff](https://github.com/vaimo/composer-patches/compare/3.7.0...3.7.1)

## 3.7.0 (2017-09-23)

### Feature

* added version branching for sequenced items
* added simplified version branching format where json object key is constraint and value the source

Links: [src](https://github.com/vaimo/composer-patches/tree/3.7.0) [diff](https://github.com/vaimo/composer-patches/compare/3.6.0...3.7.0)

## 3.6.0 (2017-08-29)

### Feature

* allow multiple patch files to be declared under same label (see: Version branching)
* allow certain patches for packages to be excluded (see: Environment variables)

### Fix

* restored backwards compatibility with PHP versions that do not support new new array markup

Links: [src](https://github.com/vaimo/composer-patches/tree/3.6.0) [diff](https://github.com/vaimo/composer-patches/compare/3.5.2...3.6.0)

## 3.5.2 (2017-08-29)

### Fix

* make sure that path normalizer does not touch root-level patch declarations

Links: [src](https://github.com/vaimo/composer-patches/tree/3.5.2) [diff](https://github.com/vaimo/composer-patches/compare/3.5.1...3.5.2)

## 3.5.1 (2017-07-10)

### Fix

* make sure that 'resetting patched package' is not shown when package is indirectly targeted

Links: [src](https://github.com/vaimo/composer-patches/tree/3.5.1) [diff](https://github.com/vaimo/composer-patches/compare/3.5.0...3.5.1)

## 3.5.0 (2017-07-10)

### Feature

* allow bundled patches (that target multiple packages) to be declared, tracked, reverted correctly when changed or removed (see: Bundled patches)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.5.0) [diff](https://github.com/vaimo/composer-patches/compare/3.4.0...3.5.0)

## 3.4.0 (2017-07-09)

### Feature

* allow dev-only patches to be declared (see: Development patches)

Links: [src](https://github.com/vaimo/composer-patches/tree/3.4.0) [diff](https://github.com/vaimo/composer-patches/compare/d07e6a00af9c7c3a3c93460ea2f024eb0e05c5fd...3.4.0)