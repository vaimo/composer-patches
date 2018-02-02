# composer-patches

Applies a patch from a local or remote file to any package that is part of a given composer project. Packages can be defined both on project and on package level.

## Quick start: explicit patch definitions

Same format is used for both project (root level scope) patches and for package patches.

```json
{
  "require": {
    "some/package": "1.2.3",
    "vaimo/composer-patches": "^3.0.0"
  },
  "extra": {
    "patches": {
      "some/package": {
        "desription about my patch": "my/file.patch",
        "other patch": "http://example.com/url/to/patch.patch"
      }
    }
  }
}
```

_This is basically the bare minimum to get your patch to apply. The rest of this README deals with extra levels of complexity that can be introduced to patch definition when a need arises: patch sequencing, verison branching, bundled patches, locked path strip level and so on..._

## Quick start: patch list definition

Same format is used for both project (root level scope) patches and for package patches. Paths are relative
to the owner of the composer.json that introduces a certain file path.

```json
{
  "require": {
    "some/package": "1.2.3",
    "vaimo/composer-patches": "^3.0.0"
  },
  "extra": {
    "patches-file": "path/to/patches.json"
  }
}
```

Where **path/to/patches.json** contains:

```json
{
  "some/package": {
    "description about my patch": "my/file.patch"
  } 
}
```

## Patch file format

The targeted file format in the patch should be relative to the patched package - or - in other words: relative
to the context it was defined for:

So it the patch is defined for my/package and my/package has a file vendor/my/package/Models/Example.php,
the patch would target it with

```diff
--- Models/Example.php.org	2017-05-24 14:13:36.449522497 +0200
+++ Models/Example.php	2017-05-24 14:14:06.640560761 +0200

@@ -31,7 +31,7 @@
      */
     protected function someFunction($someArg)
     {
-        $var1 = 123;
+        $var1 = 456;
         /**
          * rest of the logic of the function
          */
```

Note that you don't have to change the patch name or description when you change it after it has already
been used in some context by someone as the module will be aware of the patch contents and will re-apply
it when it has changed since last time.

_This example will make the patch apply with patch stripping level of '0'._

## Development patches

The patches module mimics the way composer separates development packages from normal requirements by introducing two extra keys, where exact same rules apply as for normal patch declarations: `patches-dev`, `patches-file-dev`

The patches declared under those keys will not be applied when installing the project with `--no-dev` option.

## Patch composer command

Installing the plugin will introduce a new composer command: **composer patch**

```shell
# Re-apply new patches (similar to patch apply on 'composer install') 
composer patch 

# Re-apply all patches
composer patch --redo 

# Re-apply patches for one speicif package
composer patch --redo my/package 

# Re-apply patches for one speicif package with patch name filter 
composer patch --filter wrong-time-format --filter other-file --redo my/package 

# Re-apply patches and skip filenames that contain 'wrong<anything>format'  
composer patch --filter '!wrong*format' --redo my/package 

# Reset all patched packages
composer patch --undo 

# Reset one specific patched package
composer patch --undo my/package 

# Gather patches information from /vendor instead of install.json
composer patch --from-source

# Ideal for testing out a newly added patch against my/package
composer patch --from-source --redo my/package
```

The main purpose of this command is to make the maintenance of already created patches and adding new ones as easy as possible by allowing user to test out a patch directly right after defining it without having to trigger 'composer update' or 'composer install'.

## Patcher sources

These flags allow developer to have more control over the patch collector and omit certain sources when
needed. All the sources are included by default.

```json
{
  "extra": {
    "patcher": {
      "sources": {
        "project": true,
        "vendors": true,
        "packages": true
      }    
    }
  }
}
```

Note that packages source definition can be configured to be more granular by listing all the vendors
that should be included.

```json
{
  "extra": {
    "patcher": {
      "sources": {
        "vendors": ["vaimo", "magento"]
      }    
    }
  }
}
```

For packages, wildcards can be used to source form a wider range of packages. 

```json
{
  "extra": {
    "patcher": {
      "sources": {
        "packages": ["vaimo/patches-*"]
      }    
    }
  }
}
```

In case the functionality of the plugin has to be fully disabled, developer can just set "patcher"
to "false".

```json
{
  "extra": {
    "patcher": false
  }
}
```

_These flags do not affect the way 'patch' command works, which will apply patches even when patching has
been said to be disabled in composer.json; These flags indicate whether the patches will be applied on 
'install' and 'update' calls_ 

## Multiple patch list files

Note that to enable the developer to perform occasional cleanup and sub-grouping on the patches 
declaration, multiple patches files can be defined:

```json

{
  "require": {
    "some/package": "1.2.3",
    "vaimo/composer-patches": "^3.0.0"
  },
  "extra": {
    "patches-file": ["patches.json", "legacy.json"]
  }
}
```

The files are processed sequentially and merged in a way where all the patches in all the files are 
processed (meaning: even if the declaration in both files is exactly the same, both will be processed and 
the merging will be done in very late state based on the absolute path of the patch file path).

## Sequenced patches

In case it's important to apply the patches in a certain order, use an array wrapper around the patch definitions.

```json
{
  "extra": {
    "patches": {
      "targeted/package": [
        {
          "label": "my patch description",
          "source": "my/file.patch"
        },
        {
          "label": "other patch (depends on my/file.patch being applied)",
          "source": "other/file.patch"
        }
      ]
    }
  }
}

```

When defined in the following format, `my/file.patch` will always be applied before `other/file.patch`.

## Version restriction

In case the patch is applied only on certain version of the package, a version restriction can be defined for the patch:

```json
{
  "extra": {
    "patches": {
      "targeted/package": {
        "description for first patch": {
          "source": "my/first-fix.patch",
          "version": "<1.2.3"
        },
        "second patch": {
          "source": "my/other-fix.patch",
          "depends": {
            "other/package": ">=2.1.7"
          }
        },
        "description for third patch": {
          "<1.2.3": "my/first-fix-simplified-format.patch"
        }
      }
    }
  }
}

```

The first patch in this example, expects 'targeted/package' to have version that's smaller than 1.2.3; otherwise the patch will be omitted. The second patch has a indirect dependency where it does not care what version the targeted/package is, and is checking the verison of **other/package** instead.

The third patch is just a simplified version of the first patch. It makes the declaration easier to read with the sacrifice 
of not being able to define anything else about the patch (can't define dependencies, specific patch strip level, etc).

_Version constraints are parsed by composer's own version constraint parser. All same rules apply._

## Version branching

When there are almost identical patches for different version of some package, then they can be declared
under same label like this:

```json
{
  "extra": {
    "patches": {
      "some/package": {
        "More detailed description on what the patch does": {
          "1.0.* <1.2.0": "patches/somepackage/1.0.0/important-fix.patch",
          ">=1.2.0": "patches/somepackage/1.2.0/important-fix.patch"
        }
      }
    }
  }
}
```

Same can be used while using sequenced patches ...

```json
{
  "extra": {
    "patches": {
      "magento/module-widget": [
        {
          "label": "Fix: Category tree items in admin get double-escaped due to ExtJs and Magento both doing the escaping",
          "source": {
            "<=100.1.5": "Magento_Widget/100.1.5/some-patch.patch",
            ">100.1.5": "Magento_Widget/100.1.5/some-other-patch.patch"
          }
        }      
      ]
    }
  }
}
```

## Bundled patches

In case there's a need to define a patch that targets multiple packages within a single patch file, 
alternative patch definition format is recommended:

```json
{
  "extra": {
    "patches": {
      "*": {
        "Some bundle patch": {
          "source": "path/to/bundle/patch.patch",
          "targets": [
            "vendor1/module1",
            "vendor1/module2",
            "vendor2/module3"
          ]
        }
      }
    }
  }
}
```

Patches defined like this will be applied relative to the project vendor root instead of being relative 
to the targeted package (which in this case is not really known).

Note that it's important still to have all the targeted packages listed as they'd need to be re-installed 
in case the patch changes or patch-reapply is called (see below for the environment variable that allows
that to be triggered). 

_In case targets for bundled patch are not defined, the code will peek into the patch file and try to 
resolve the targets from the contents of the patch. Note that this feature is somewhat experimental and 
developer is still strongly encouraged to have the patch targets defined explicitly._

## Excluding patches

In case some patches that are defined in packages have to be excluded from the project (project has custom verisons of the files, conflicts with other patches, etc), exclusions records can be defined in the project's composer.json:

```json
{
  "extra": {
    "excluded-patches": {
      "patch/owner": [
        "path/to/file.patch"
      ]
    }
  }
}
```

Will exclude the a patch that was defined in a package in following (or similar) manner ...

```json
{
  "name": "patch/owner",
  "extra": {
    "patches": {
      "targeted/package": {
        "Some patch description": "path/to/file.patch"
      }
    }
  }
}
```

The important part to note here is to remember that exclusion ignores patch target and focuses on the owner
instead. Description is also not part of the exclusion logic.

## Defining patches with strict path strip level

By default, the patcher will try to apply the patch with several path stripping options - in some cases 
this is not something that one wants to allow - for example: if the patch is in full extent just creating 
new files, it might end up creating them to wrong directories. In some cases, some patches might have 
unconventional path definitions that derive from other project patches. Rather than changing the global
settings, it's possible to define custom ones for just one patch.

```json
{
  "name": "patch/owner",
  "extra": {
    "patches": {
      "targeted/package": {
        "Some patch description": {
          "source": "path/to/file.patch",
          "level": "0"
        }
      }
    }
  }
}
```

Note that same can be done in case the patch has been split between multiple target versions, but using 
the following format:

```json
{
  "name": "patch/owner",
  "extra": {
    "patches": {
      "targeted/package": {
        "Some patch description": {
          "source": {
            "<=1.2.3": "path/to/1.2.3/file.patch",
            ">1.2.3": "path/to/1.2.4/file.patch"
          },
          "level": "0"
        }
      }
    }
  }
}
```

Note that in case most of the patches that you apply use same level, which is not covered by the default
configuration, you can change the setting globally by overriding the patcher configuration:

```json
{
  "extra": {
    "patcher": {
      "levels": [5]
    },
    "patches": {
      "targeted/package": {
        "Some patch description": {
          "source": "path/to/1.2.3/file.patch"
        }
      }
    }
  }
}
```

## Skipping patches

In case there's a need to temporarily fast-exclude patches which is usually the case when going through
maintenance or upgrade of the underlying project's framework, a skip flag can be used to pass over certain 
declaration lines.

**NOTE: it's useful to use this in combination with the COMPOSER_PATCHES_PREFER_OWNER env flag**

```json
{
  "name": "patch/owner",
  "extra": {
    "patches": {
      "targeted/package": {
        "Some patch description": "path/to/file.patch#skip"
      }
    }
  }
}
```

## Patcher Configuration

In case it's needed for the patcher to apply the patches using some third-party application or to include
some extra options, it's possible to declare new patcher commands or override the existing ones by adding 
a new section to the "extra" of the project's composer.json. Note that this example is a direct copy of what
is built into the plugin. Changes to existing definitions are applied recursively.

```json
{
  "extra": {
    "patcher": {
      "sources": {
        "project": true,
        "packages": true,
        "vendors": true
      },
      "appliers": {
        "GIT": {
          "ping": "!cd .. && [[bin]] rev-parse --is-inside-work-tree",
          "bin": "which git",
          "check": "[[bin]] apply -p{{level}} --check {{file}}",
          "patch": "[[bin]] apply -p{{level}} {{file}}"
        },
        "PATCH": {
          "bin": ["which custom-patcher", "which patch"],
          "check": "[[bin]] -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}",
          "patch": "[[bin]] -p{{level}} --no-backup-if-mismatch < {{file}}"
        }
      },
      "operations": {
        "ping": "Usability test",
        "bin": "Availability test",
        "check": "Patch validation",
        "patch": "Patching"
      },
      "sequence": {
        "appliers": ["PATCH", "GIT"],
        "operations": ["bin", "ping", "check", "patch"]
      },
      "levels": [0, 1, 2]
    }
  }
}
```

_Multiple alternative commands can be defined for each operation. Operation itself is considered to be 
success when at least one command call results in a SUCCESS return code_ 

_Patch is considered to be applied when all operations can be completed with SUCCESS return code_
 
_Exclamation mark in the beginning of an operation will be translated as 'failure is expected'_

_The values of 'level', 'file' and 'cwd' variables are populated by the plugin, rest of the variables 
get their value from the response of the operations that have already been processed. This means that 
'bin' value will be the result of 'bin' operation. Note that if sequence places 'bin' after 'check' or 
'patch', then the former will be just removed from the template_

_The [[]] will indicate the value is used as-is, {{}} will make the value be shell-escaped_

Appliers are executed in the sequence dictated by sequence where several path levels are used with 
validation until validation success is hit. Note that each applier will be visited before moving on to 
next path strip level, which result in sequence similar to this:

    PATCH:0 GIT:0 PATCH:1 GIT:1 ...

## Environment variables

* COMPOSER_PATCHES_REAPPLY_ALL - will force all patches to be re-applied
* COMPOSER_PATCHES_FATAL_FAIL - exit after first patch failure is encountered
* COMPOSER_PATCHES_SKIP_PACKAGES - comma-separated package names to exclude from patching, useful 
  when maintaining patches on package upgrade. Does not affect bundled patches.
* COMPOSER_PATCHES_PREFER_OWNER - always use data directly from owner's composer.json rather than 
  using the information stored in installed.json
* COMPOSER_PATCHES_SKIP_CLEANUP - Will leave packages patched even when vaimo/composer-patches is 
  removed. By default, patched packages are re-installed to reset the patches (useful when creating 
  immutable build artifacts without any unnecessary modules installed).

## Upgrading the module

When upgrading the module, one might encounter odd crashes about classes not being found or class 
constructor arguments being wrong. 

This usually means that the class structure or constructor footprint in some of the classes have changed 
after the upgrade which means that the plugin might be running with some classes from the old and some 
classes from the new version. 

Due to the fact that the patcher kicks in very late in the process of installing a project (before 
auto-loader generation), developers are advised to re-execute 'composer install'.

## Changelog 

List of generalized changes for each release.

### 3.19.1

* Maintenance: documentation re-organized to prioritize quick comprehension on the basics of the 
  module's functionality.
* Maintenance: minor code restyle changes.

### 3.19.0

* Feature: added new operation to check if applier is available (can be used to exclude certain 
  appliers on certain systems).
* Feature: added new operation to find applier's executable and use it in the operations that 
  come afterwards.
* Feature: allow multiple commands per operation.
* Feature: introduced the possibility to define an operation that is considered a success when 
  the command does not succeed. 
* Fix: removed references to $this within closures (as it's not supported in 5.3.X). 

### 3.18.0

* Feature: several config keys renamed (patchers => appliers, patcher-config => patcher). Backwards compatible.
* Feature: patch enabling moved under patcher/sources (project:bool, packages:bool|array, 
  vendors:bool|array). Backwards compatible.
* Feature: allow granular patch sources inclusion (so that only some vendors would be included).
* Feature: allow some providers to have special extra operations (before this change, every 
  applier was expected to have every listed operation declared).

### 3.17.3

* Fix: switched to using PHP constants for OS-related values like newline and path separator.
* Maintenance: switched to using constants for each free-text array key value + reduced code repetition.
* Maintenance: logger indentation reworked not to be hardcoded in log messages in text form to open up the 
  chance to switch to different logging methods/formats in the future. 

### 3.17.2

* Fix: patches not registered for patch target packages when 'COMPOSER_PATCHES_FATAL_FAIL' enabled and 
  error is encountered.

### 3.17.1

* Fix: composer patch command option 'undo' not working correctly when patching explicitly set to be 
  enabled in composer.json of the project.
* Fix: composer patch command option 'redo' not working correctly when patching explicitly set to be 
  enabled in composer.json of the project.
* Fix: using 'undo' and 'redo' together still triggers 'undo' functionality.

### 3.17.0

* Fix: don't force bundle patches to have 'vendor' in their paths as it's a customizable value
* Fix: patch information not correctly restored to installed.json when there were no patch updates while 
  running 'composer update'.
* Maintenance: code split into smaller classes where applicable to move towards single-responsibility 
  entities in design.
* Maintenance: reduced the mess around re-using same terminology in too many different contexts.

### 3.16.0

* Feature: auto-resolve bundle patch targets when 'targets' not defined.
* Feature: multiple filters for patch command.
* Feature: allow patch command path filter to have wildcards and negation. 
* Fix: ignore custom 'targets' config for non-bundled patches.
* Fix: bundled patch was not registering/resetting target packages when performing redo/undo.
* Fix: patches-dev and patches-file not enabling patching by default when defined on project level.
* Fix: having patcher enabled only on project level did not compile patch queue correctly when disabling 
  the option of including patches from packages.

### 3.15.0

* Feature: allow multiple patch files to be defined to enable high-level patch grouping (or to allow 
  occasional cleanup where really old legacy patches could be moved elsewhere).
* Fix: patch files not loaded from relative path even when they belong to a package rather than being 
  referred directly from the project.

### 3.14.1

* Fix: crash when trying to declare path stripping level for version-branched sources 

### 3.14.0

* Feature: allow certain patches to be processed only with very strict path strip options and patcher type.
* Feature: changed patcher definition template to use variable markup rather than relying on sprintf 
  patterns which dictates the variables in the template to be defined in certain order.
* Feature: allow extra operations to be defined or the sequence of existing ones to be changed.
* Fix: made sure that no compact array markup is used within the plugin.
* Maintenance: changed the 'validate' in patcher configuration key to 'check'. Support for 'validate' kept.

### 3.13.2

* Maintenance: updated lock to latest due to composer validate error.

### 3.13.1

* Fix: roll-back with 'undo' to reset package when used with specific targets.

### 3.13.0

* Feature: option to apply only some of the patches based on text-based file name filter.
* Feature: added an option for the user to have control over the sequence of the patchers.
* Fix: patch path strip levels re-ordered to go sequentially from 0 to 2 to allow first run to be 
  with 'as is' path.
* Fix: changed patch applier logic to test different patchers with same level rather than going 
  through all patches with levels in sequence.
* Fix: preferring standard patcher instead of starting with GIT.
* Fix: patches not being reset when removing all patches from patch provider in vendor folder and 
  running '--from-source --redo my/package'.

### 3.12.1

* Feature: renamed 'reset' to 'redo' to make the command argument's purpose easier to understand when 
  compared with 'redo'.
* Fix: properly re-apply all patches when using 'from-source' nad 'redo' arguments together.

### 3.12.0

* Feature: introduced a new composer command to make it easier to re-apply all patches and give 
  newly defined patches a quick test-run (composer patch).

### 3.11.0

* Feature: reset all patched packages when vaimo/composer-patches in removed from a project (with an 
  option of leaving the patches applied).
* Feature: added the possibility for a project to define custom patch appliers or override the ones 
  that are built into the package (see: Patcher Configuration).
* Fix: avoid crashing at the end of a composer operation when vaimo/composer-patches was removed while 
  it was executing, but it's plugin class remains loaded and triggers an action after all 
  install/uninstall actions are done.

### 3.10.4

* Maintenance: changes to package meta-data.

### 3.10.3

* Fix: crash due to "missing array key" that's caused by bad comparison in code when using only 'depends' 
  on certain patch declarations.

### 3.10.2

* Fix: declaration of 'depends' was overriding 'version'. Constraints defined on those keys are now merged.
* Fix: patch applied when single version constraint was matched even when multiple ones defined.

### 3.10.1

* Maintenance: changes to package metadata.

### 3.10.0

* Feature: environment variable names standardized (old names still supported).
* Fix: patches not re-applied when package is upgraded (old 'applied_patches' incorrectly restored instead).
* Fix: root package ignored when using COMPOSER_PATCHES_PREFER_OWNER.
* Maintenance: Code re-organized to centralize the access to env flags.

### 3.9.0

* Feature: added new environment flag to force patcher to extract the patch info from vendor folder instead 
  of using the information from installed.json (mainly for patch maintenance). 

### 3.8.1

* Fix: process every source path and check for 'skip' flag. In certain cases, the source-path flag was ignored.  

### 3.8.0

* Feature: allow patches to be skipped by adding 'skip' flag in it's definition (good as maintenance flags 
  when doing major base-framework upgrades).
* Fix: excluded patches required develop to specify patch owner vendor path instead of just the path that 
  was relative to the patch owner folder.

### 3.7.1

* Maintenance: code cleanup (some debugging code removed).

### 3.7.0

* Feature: added version branching for sequenced items.
* Feature: added simplified version branching format where json object key is constraint and value the source.

### 3.6.0

* Feature: allow multiple patch files to be declared under same label (see: Version branching).
* Feature: allow certain patches for packages to be excluded (see: Environment variables).
* Fix: restored backwards compatibility with PHP versions that do not support new new array markup.

### 3.5.2

* Fix: make sure that path normalizer does not touch root-level patch declarations.

### 3.5.1

* Fix\Cosmetic: make sure that 'resetting patched package' is not shown when package is indirectly targeted.

### 3.5.0

* Feature: allow bundled patches (that target multiple packages) to be declared, tracked, reverted correctly 
  when changed or removed (see: Bundled patches).

### 3.4.0

* Feature: allow dev-only patches to be declared (see: Development patches).
