# composer-patches

Simple patches plugin for Composer. Applies a patch from a local or remote file to any package required with composer.

_The information about applied patches on local installed project will be only kept in the installed.json._

## Enabling patching for a project

Patching is enabled when:

* project has "patches" key defined under "extra" 
* project has "enable-patching" key defined under "extra" 

_The latter is only useful if you have no patches defined directly on the root/project level as the default state of the patches enabled/disabled state will be: disabled_

```
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

```
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

```
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

```

{
  "patches": {
    "some/package": {
      "desription about my patch": "my/file.patch"
    }
  }
}

```

## Patch file path format

Please note that in both cases the patch path should be relative to the context where it's defined:

* For project, it should be relative to project root (relative to **{project}**)
* For package, it should be relative to package root (relative to **{project}/vendor/myvendor/module**)

## Using patch url

Patches can be stored in remote location and referred to by using the full URL of tha patch.

```
{
  "patches": {
    "vendor/project": {
      "Patch title": "http://example.com/url/to/patch.patch"
    }
  }
}
```

## Sequencing the patches

In case it's important to apply the patches in a certain order, use an array wrapper around the patch definitions.

```
{
  "extra": {
    "patches": {
      "targeted/package": [
        {
          "label": "my patch description",
          "source": "my/file.patch"
        }
      ]
    }
  }
}

```

Note that this way of declaring the patches also support versioning and remote patches (in which case one should use "url" key).

## Version restriction

In case the patch is applied only on certain version of the package, a version restriction can be defined for the patch:

```
{
  "extra": {
    "patches": {
      "targeted/package": {
        "description for my patch": {
          "url": "my/file.patch",
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

```
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

## Development patches

In case there's a need to include patches just for the sake of development convenience, an alternative
sub-group can be defined is similar manner to how one would define development packages in project context
 
 ```
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

## Excluding patches

In case some patches that are defined in packages have to be excluded from the project (project has custom verisons of the files, conflicts with other patches, etc), exclusions records can be defined in the project's composer.json:

```
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

```
{
  "name": "patch/owner",
  "extra": {
    "patches": {
      "targeted/package": {
        "desription about my patch": "path/to/file.patch"
      }
    }
  }
}

```

The important part to note here is the file-path and patch owner. Description is not part of the exclusion logic.

## Hints on creating a patch 

The file-paths in the patch should be relative to the targeted package root and start with ./

This means that patch for a file <projet>/vendor/my/package/some/file.php whould be targeted in the patch as ./some/file.php

Note that you don't have to change the patch name or description when you change it after it has already
been used in some context by someone as the module will be aware of the patch contents and will re-apply
it when it has changed since last time.

## Environment variable feature flags

* COMPOSER_FORCE_PATCH_REAPPLY - will force all patches to be re-applied
* COMPOSER_EXIT_ON_PATCH_FAILURE - exit after first patch failure is encountered

## Difference between this and netresearch/composer-patches-plugin

* Gathers patches from both root/project composer.json AND from packages
* This plugin is much more simple to use and maintain
* This plugin doesn't require you to specify which package version you're patching (but you'll still have the option to do so).
* This plugin is easy to use with Drupal modules (which don't use semantic versioning).

## Credits

Heavily modified version of https://github.com/cweagans/composer-patches

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is
abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex
and difficult to use.
