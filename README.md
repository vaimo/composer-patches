# composer-patches

Simple patches plugin for Composer. Applies a patch from a local or remote file to any package required with composer.

_The information about applied patches on local installed project will be kept in the installed.json (simple 
boolean flag for patched packages will be also included when running composer update command)_

## Enabling patching for a project

Patching is enabled when:

* project has "patches" key defined under "extra" 
* project has "enable-patching" key defined under "extra" 

_The latter is only useful if you have no patches defined directly on the root/project level as the default state of the patches enabled/disabled state will be: disabled_

```json
{
  "extra": {
    "patches": {},
    "enable-patching": true,
    "enable-patching-from-packages": false
  }
}
```

* **enable-patching** - In case you have patches defined on root/project level, it's not required to
  have **enable-patching** key to be defined unless you want to explicitly disable the functionality.
* **enable-patching-from-packages** - Enabled by default (when not defined), can be used to omit all patches
  from packages. For more granular exclusion, see "Excluding patches" sub-secion.

When patching is disabled and **composer install** is re-executed, all patched package will be re-installed
(to wipe the patched in changes).



## Usage: patch file

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
        "desription about my patch": "my/file.patch"
      }
    }
  }
}
```

## Usage: patch list file

Same format is used for both project (root level scope) patches and for package patches.

```json
{
  "require": {
    "some/package": "1.2.3",
    "vaimo/composer-patches": "^3.0.0"
  },
  "extra": {
    "patches-file": "path/to/composer.patches.json"
  }
}
```

In which case the file should contain patches listed in following format:

```json
{
  "patches": {
    "some/package": {
      "desription about my patch": "my/file.patch"
    }
  }
}
```

## Patch definition in composer.json

The format of a patch definition has several levers of complexity to cater for usage in context of different
frameworks.

```json
{
  "patches": {
    "some/package": {
      "desription about my patch": "my/file.patch"
    }
  }
}
```

Which is the same as (which allows optional version restrictions) ... 

```json
{
  "patches": {
    "some/package": {
      "desription about my patch": {
        "source": "my/file.patch"
      }
    }
  }
}
```

Which is the same as (which allows optional patch sequencing) ... 

```json
{
  "patches": {
    "some/package": [
      {
        "label": "Fix: will be applied first",
        "source": "my/file.patch"
      },
      {
        "label": "Fix: will be applied after the first",
        "source": "my/other-file.patch"
      }
    ]
  }
}
```

In case there's no need to add version restrictions or sequence patches, the simple format use is recommended.

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

## Patch URL

Patches can be stored in remote location and referred to by using the full URL of tha patch.

```json
{
  "patches": {
    "vendor/project": {
      "Patch title": "http://example.com/url/to/patch.patch"
    }
  }
}
```

Note that in case of other patch definition formats, the url of the patch file should be defined 
under "url" key of the patch definition (instead of "source").

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
        "description for my patch": {
          "source": "my/file.patch",
          "version": "~1.2.3"
        }
      }
    }
  }
}

```

The version version constraint defintion supports all version definition patterns supported by the version
of Composer.

In projects that rely on certain framework's base package version (which will always guarantee that the patch
targeted package is always on certain version) alternative format may be more suitable:

```json
{
  "extra": {
    "patches": {
      "magento/module-swatches": {
        "Fix: https://github.com/magento/magento2/issues/7959": {
          "source": "Magento_Swatches/100.1.2/fix-javascript-crash-when-all-options-not-selected.patch",
          "depends": {
            "magento/magento2-base": ">=2.1.7"
          }
        }
      }
    }
  }
}
```

The patch will be applied if at least ONE indirect dependency ends up being a version constrain match.

## Version branching

When there are almost identical patches for different version of some package, then they can be declared
under same label like this:


```json
{
  "extra": {
    "patches": {
      "magento/module-sales": {
        "Fix: Wrong time format for orders in admin grid": {
          "Magento_Sales/100.1.6/fix-wrong-time-format-for-orders-in-admin-grid.patch": {
            "version": "100.1.* <100.1.7"
          },
          "Magento_Sales/100.1.7/fix-wrong-time-format-for-orders-in-admin-grid.patch": {
            "version": ">=100.1.7"
          }
        }
      }
    }
  }
}
```

Note that indirect version dependency can be used in this case as well (see the "depends" example above).

Same can be achieved with sequenced patches ...

```json
{
  "extra": {
    "patches": {
      "magento/module-widget": [
        {
          "label": "Fix: Some description",
          "source": "some-source.patch"
        },
        {
          "label": "Fix: Category tree items in admin get double-escaped due to ExtJs and Magento both doing the escaping",
          "source": {
            "Magento_Widget/100.1.5/other-patch.patch": {
              "version": "<=100.1.5"
            },
            "Magento_Widget/100.1.5/avoid-double-escaping-special-chars-and-quotes-for-extjs-tree-item-names.patch": {
              "version": ">100.1.5"
            }
          }
        }      
      ]
    }
  }
}
```

In both of the above cases, a simplified definition format can be used

```json
{
  "extra": {
    "patches": {
      "magento/module-sales": {
        "Fix: Wrong time format for orders in admin grid": {
          "100.1.* <100.1.7": "Magento_Sales/100.1.6/fix-wrong-time-format-for-orders-in-admin-grid.patch",
          ">=100.1.7": "Magento_Sales/100.1.7/fix-wrong-time-format-for-orders-in-admin-grid.patch"
        }
      }
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

Patches defined like this will be applied relative to the project root instead of being relative to the
targeted package (which in this case is not really known).

Note that it's important still to have all the targeted packages listed as they'd need to be re-installed 
in case the patch changes or patch-reapply is called (see below for the environment variable that allows
that to be triggered).

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
        "Some patch description": {
          "source": "path/to/file.patch",
          "skip": true
        }
      }
    }
  }
}
```

Same could be achieved when using the brief format by adding #skip to the end of the patch filename ...

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

## Development patches

In case there's a need to include patches just for the sake of development convenience, an alternative
sub-group can be defined is similar manner to how one would define development packages in project context
 
```json
{
  "extra": {
    "patches-dev": {
      "symfony/console": {
        "Development: suppress deprecation warnings for classes used by magento (needed for latest codeception)": {
          "source": "patches/Symfony_Console/2.7.0/suppress-deprecation-warnings.patch",
          "version": ">=v2.7.0"
        }
      }
    }
  }
}
```

These patches will not be applied when installing the project with `--no-dev` option.
 
Note that same definition pattern can be used for patches-file, where the key would become `patches-file-dev`
and patch list inside the file would still use the key `patches`.

## Commands

Full list of commands that this plugin introduces:

1. **composer patch** -- Triggers patcher in different execution modes

```shell
# Re-apply all patches
composer patch --redo 

# Re-apply patches for one speicif package
composer patch --redo my/package 

# Re-apply missing patches (similar to patch apply on 'composer install') 
composer patch 

# Gather patches information from /vendor instead of install.json
composer patch --from-source 

# Reset all patched packages
composer patch --reset 

# Reset one specific patched package
composer patch --redo my/package 
```

## Patcher Configuration

In case it's needed for the patcher to apply the patches using some third-party application or to include
some extra options, it's possible to declare new patcher commands or override the existing ones by adding 
a new section to the "extra" of the project's composer.json. Note that this example is a direct copy of what
is built into the plugin. Changes to existing definitions are applied recursively.

```json
{
  "extra": {
    "patcher-config": {
      "patchers": {
        "GIT": {
          "validate": "git apply --check -p%s %s",
          "patch": "git apply -p%s %s"
        },
        "PATCH": {
          "validate": "patch -p%s --dry-run --no-backup-if-mismatch < %s",
          "patch": "patch -p%s --no-backup-if-mismatch < %s"
        }
      },
      "levels": [1, 0, 2]
    }
  }
}
```

## Environment variables

* COMPOSER_PATCHES_REAPPLY_ALL - will force all patches to be re-applied
* COMPOSER_PATCHES_FATAL_FAIL - exit after first patch failure is encountered
* COMPOSER_PATCHES_SKIP_PACKAGES - comma-separated package names to exclude from patching, useful when 
  maintaining patches on package upgrade. Does not affect bundled patches.
* COMPOSER_PATCHES_PREFER_OWNER - always use data directly from owner's composer.json rather than using the 
  information stored in installed.json
* COMPOSER_PATCHES_SKIP_CLEANUP - Will leave packages patched even when vaimo/composer-patches is removed. 
  By default, patched packages are re-installed to reset the patches (useful when creating immutable build 
  artifacts without any unnecessary modules installed).

### Deprecated flag names

* COMPOSER_FORCE_PATCH_REAPPLY => COMPOSER_PATCHES_REAPPLY_ALL.
* COMPOSER_EXIT_ON_PATCH_FAILURE => COMPOSER_PATCHES_FATAL_FAIL.
* COMPOSER_SKIP_PATCH_PACKAGES => COMPOSER_PATCHES_SKIP_PACKAGES.

## Credits

Heavily modified version of https://github.com/cweagans/composer-patches

## Changelog 

List of generalized changes for each release.

### 3.12.0

* Feature: Introduced a new composer command to make it easier to re-apply all patches and give newly defined
  patches a quick test-run (see: Commands)

### 3.11.0

* Feature: Reset all patched packages when vaimo/composer-patches in removed from a project (with an option 
  of leaving the patches applied).
* Feature: Added the possibility for a project to define custom patch appliers or override the ones that are 
  built into the package (see: Patcher Configuration)
* Fix: Avoid crashing at the end of a composer operation when vaimo/composer-patches was removed while it was 
  executing, but it's plugin class remains loaded and triggers an action after all install/uninstall actions 
  are done.

### 3.10.4

* Maintenance: changes to package meta-data

### 3.10.3

* Fix: crash due to "missing array key" that's caused by bad comparison in code when using only 'depends' on certain patch declarations

### 3.10.2

* Fix: declaration of 'depends' was overriding 'version'. Constraints defined on those keys are now merged.
* Fix: patch applied when single version constraint was matched even when multiple ones defined

### 3.10.1

* Maintenance: changes to package metadata.

### 3.10.0

* Feature: environment variable names standardized (old names still supported).
* Fix: Patches not re-applied when package is upgraded (old 'applied_patches' incorrectly restored instead).
* Fix: Root package ignored when using COMPOSER_PATCHES_PREFER_OWNER.
* Maintenance: Code re-organized to centralize the access to env flags.

### 3.9.0

* Feature: Added new environment flag to force patcher to extract the patch info from vendor folder instead 
  of using the information from installed.json (mainly for patch maintenance). 

### 3.8.1

* Fix: process every source path and check for 'skip' flag. In certain cases, the source-path flag was ignored.  

### 3.8.0

* Feature: Allow patches to be skipped by adding 'skip' flag in it's definition (good as maintenance flags 
  when doing major base-framework upgrades).
* Fix: excluded patches required develop to specify patch owner vendor path instead of just the path that 
  was relative to the patch owner folder.

### 3.7.1

* Maintenance: Code cleanup (some debugging code removed).

### 3.7.0

* Feature: Added version branching for sequenced items.
* Feature: Added simplified version branching format where json object key is constraint and value the source.

### 3.6.0

* Feature: Allow multiple patch files to be declared under same label (see: Version branching).
* Feature: Allow certain patches for packages to be excluded (see: Environment variables).
* Fix: Restored backwards compatibility with PHP versions that do not support new new array markup.

### 3.5.2

* Fix: Make sure that path normalizer does not touch root-level patch declarations.

### 3.5.1

* Fix\Cosmetic: Make sure that 'resetting patched package' is not shown when package is indirectly targeted.

### 3.5.0

* Feature: Allow bundled patches (that target multiple packages) to be declared, tracked, reverted correctly 
  when changed or removed (see: Bundled patches).

### 3.4.0

* Feature: Allow dev-only patches to be declared (see: Development patches).
