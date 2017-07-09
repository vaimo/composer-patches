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

## Patch definition format

The format of a patch definition has several levers of complexity to cater for usage in context of different
frameworks.

```

{
  "patches": {
    "some/package": {
      "desription about my patch": "my/file.patch"
    }
  }
}

```

Which is the same as (which allows optional version restrictions) ... 

```

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

```

{
  "patches": {
    "some/package": [
        {
          "label": "desription about my patch",
          "source": "my/file.patch"
        }
      ]
    }
  }
}

```

In case there's no need to add version restrictions or sequence patches, the simple format use is recommended.

## Patch file path format

The targeted file format in the patch should be relative to the patched package - or - in other words: relative
to the context it was defined for:

So it the patch is defined for my/package and my/package has a file vendor/my/package/Models/Example.php,
the patch would target it with

```
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

Note that in case of other patch definition formats, the url of the patch file should be defined 
under "url" key of the patch definition (instead of "source").

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

```
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

## Bundled patches

In case there's a need to define a patch that targets multiple packages within a single patch file, 
alternative patch definition format is recommended:

```
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
 
Note that same definition pattern can be used for patches-file, where the key would become `patches-file-dev`
and patch list inside the file would still use the key `patches`.

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
