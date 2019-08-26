# Vaimo Composer Patches

[![Latest Stable Version](https://poser.pugx.org/vaimo/composer-patches/v/stable)](https://packagist.org/packages/vaimo/composer-patches)
[![Build Status](https://travis-ci.org/vaimo/composer-patches.svg?branch=master)](https://travis-ci.org/vaimo/composer-patches)
[![Total Downloads](https://poser.pugx.org/vaimo/composer-patches/downloads)](https://packagist.org/packages/vaimo/composer-patches)
[![Daily Downloads](https://poser.pugx.org/vaimo/composer-patches/d/daily)](https://packagist.org/packages/vaimo/composer-patches)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/vaimo/composer-patches.svg)](https://php.net/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vaimo/composer-patches/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vaimo/composer-patches/?branch=master)
[![Code Climate](https://codeclimate.com/github/vaimo/composer-patches/badges/gpa.svg)](https://codeclimate.com/github/vaimo/composer-patches)
[![License](https://poser.pugx.org/vaimo/composer-patches/license)](https://packagist.org/packages/vaimo/composer-patches)

Applies a patch from a local or remote file to any package that is part of a given composer 
project. Patches can be defined both on project and on package level in package config or 
separate JSON file. Declaration-free mode (using embedded info within patch files) is available as well.

The way the patches are applied (the commands, pre-checks) by the plugin is fully configurable (including the 
actual commands that are executed to apply the patch) from the composer.json of the project.

Note that the plugin is kept on very old PHP version as legacy software is usually the most common context
where patches are needed.

More information on recent changes [HERE](./CHANGELOG.md).

## Overview

Composer packages can be targeted with patches in two ways: via embedded metadata (recommended approach) and 
through JSON declaration.

### Embedded Metadata

```json
{
  "require": {
    "some/package-name": "1.2.3"
  },
  "extra": {
    "patches-search": "patches"
  }
}
```

Contents of patches/changes.patch:

```diff
This patch changes... 
absolutely everything

@package some/package-name

--- Models/Example.php.org
+++ Models/Example.php
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

### JSON Declaration

```json
{
  "require": {
    "some/package-name": "1.2.3"
  },
  "extra": {
    "patches": {
      "some/package-name": {
        "This patch changes ... absolutely everything": "patches/changes.patch"
      }
    }
  }
}
```

Contents of patches/changes.patch:

```diff
--- Models/Example.php.org
+++ Models/Example.php
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

## Configuration: overview

Patches are declared under the following keys in composer.json of the patch owner (may it be project or
a package).

```json
{
  "extra": {
    "patches": {},
    "patches-file": [],
    "patches-search": [],
    "patcher": {},
    "patcher-<os_type>": {}
  }
}
```

Where the different groups have the following meaning:

* **patches** - allows patches to be defined in same file.
* **patches-file** - allows patches to be stored in another file. Can be a single file or list of files.
* **patches-search** - (>=3.28.0) scans for patch files in defined directory, relies on embedded info 
  within the patch. Can be a single path reference or a list of paths. 
  
_All paths are relative to package root._

The patches module mimics the way composer separates development packages from normal requirements by 
introducing two extra keys, where exact same rules apply as for normal patch declarations.

```json
{
  "extra": {
    "patches-dev": {},
    "patches-file-dev": [],
    "patches-search-dev": []
  }
}
```

The patches declared under those keys will NOT be applied when installing the project with `--no-dev` option.

## Basic Usage: configuring a patch with embedded metadata

This example uses the simplest way that the plugin allows you to include a patch in your project and 
relies on embedded patch target information within the patch file. 

Alternatively, same information can be provided in a [JSON format](#basic-usage-configuring-a-patch-via-composerjson) 
either directly in package's composer.json or in a separate file that composer.json refers to.

#### 1. composer.json

Configure the root folder from where the plugin should search for patches.

```json
{
  "extra": {
    "patches-search": "patches"
  }
}
```
Can be done for root package or a library/module/component package.

#### 2. file system

Create folder `<module-root/project-root>/patches` and move `whatever-your-patch-is-called.patch` to 
that folder.

The searching for patches from that folder will be recursive so developer is free to place the file
in any sub-folder as long as it's in the mentioned root folder.

#### 3. edit patch contents

Edit the `whatever-your-patch-is-called.patch` and define ...

```diff
This patch changes... 
absolutely everything

@package some/package-name

--- Models/Example.php.org
+++ Models/Example.php
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

The path `Models/Example.php` is relative to the root of `some/package-name`. 

Version constraint can be used by defining `@version >=1.2.3`.

All available/usable tags listed in [patch declaration with embedded target information](#patches-patch-declaration-with-embedded-target-information).

Alternatively the patch can be targeted with configuring it via [providing the declaration in composer.json](#basic-usage-configuring-a-patch-via-composerjson).

#### 4. (optional) make sure the patch is configured to target correct package

Check that the new patch is visible to the applier

```shell
composer patch:list --from-source --status new
```

The output should be something similar to this:

```shell
some/package-name
  ~ vaimo/patch-owner: patches/path/hatever-your-patch-is-called.patch [NEW]
    This patch changes... 
    absolutely everything
```

#### 5. (optional) make sure the patch actually applies properly

Test out the added patch (in project root).

```shell
composer patch:apply --from-source some/package-name

# Alternative to 'patch:apply' way of testing it repeatedly
composer patch:redo --from-source some/package-name 
```

The patch will be automatically applied on every composer install, update when required (when it's 
found that it's not yet installed).

```shell
Writing lock file
Generating autoload files
Processing patches configuration
  - Applying patches for some/package-name (1)
    ~ some/patch-owner: whatever-your-patch-is-called.patch [NEW]
      This patch changes... 
      absolutely everything
```

All patches are applied when after all packages are installed to allow any package to provide patches 
for any other package.

#### 6. (demo) simulate normal flow of applying the patch

```shell
composer patch:undo # remove all patches (in case optional steps were followed)
composer install # will trigger install AND re-apply the patch
composer install # second run; just to illustrate that already applied patches remain applied
```

By default, the patches will be applied in non-graceful mode: first failure will cause a fatal exception
and the whole process will halt. In case it's required for the composer command run to continue without
halting, a specific [environment variable](#environment-variables) or patch command `--graceful` flag 
can be used.

Note that in case patches are provided by a dependency package, the `composer install` will NOT work
right away as the patches folder root information is not yet available in installed.json, which does 
require the package (that owns the patches) to be installed on the same (or newer) changeset that introduced
the `patches-search` config value. 

## Basic Usage: configuring a patch via composer.json

The way of defining the patches works for:

* root/project composer.json 
* dependency packages (if you want to define your patches in a distributable package)

```json
{
  "require": {
    "some/package": "1.2.3"
  },
  "extra": {
    "patches": {
      "some/package": {
        "example local patch": "path/to/patches/relative/to/patch/owner/some-fix.patch",
        "example remote patch": "http://www.example.com/remote-patch.patch"
      }
    }
  }
}
```

* Paths to patches are relative to the owner of the composer.json that introduces a certain file path.
* Paths within patch files are relative to targeted package's root (might differ based on path-strip level).

If your patches are declared in some sub-folder, it's possible to define a base-folder that would be added
in front of all file-path based patch definitions.

```json
{
  "extra": {
    "patches-base": "path/to/patches"
  }
}
```

In this case you can define patches without having to repeatedly use the same base-path for every patch 
definition.

## Basic Usage: configuring a separate patches.json file

The way of defining the patches works for:

* root/project composer.json 
* dependency packages (if you want to define your patches in a distributable package)

```json
{
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

## Basic Usage: multiple patch list files

Note that to enable the developer to perform occasional cleanup and sub-grouping on the patches 
declaration, multiple patches files can be defined:

```json
{
  "extra": {
    "patches-file": ["patches.json", "legacy.json"]
  }
}
```

The files are processed sequentially and merged in a way where all the patches in all the files are 
processed (meaning: even if the declaration in both files is exactly the same, both will be processed and 
the merging will be done in very late state based on the absolute path of the patch file path).

## Basic Usage: comments in patch declaration

In case user wants to add extra comments to patch declaration file, any key that start with "_" can be
used. Works on any level of the patch declaration.

```json
{
  "_comment": "This patch file should hold patches that make world a better place",
  "whole/world": {
    "_excuse": "I really need this one",
    "Fix: get closer to ending poverty": "patches/provide-affordable-education.patch"
  },
  "_note": "This is another comment"
}
```

## Basic Usage: patch file format

Patches are applied relative to the root of the composer package that the patch is targeting: the file 
paths in the patch should reflect that.

So if the patch is defined for my/package and my/package has a file vendor/my/package/Models/Example.php,
the patch would target it with

```diff
--- Models/Example.php.org
+++ Models/Example.php
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

_Path stripping levels can be defined/modifier/added to allow patches with different relative paths for targeted files to also apply._

## Basic Usage: disabling patching

In case the functionality of the plugin has to be fully disabled, developer can just set "patcher"
to "false".

```json
{
  "extra": {
    "patcher": false
  }
}
```

## Patches: sequenced patches

In case it's important to apply certain patches in a certain order, use before/after directives. Note that 
you can use partial names (instead of using full path) and wildcards to target patches. 

```json
{
  "extra": {
    "patches": {
      "targeted/package": {
        "will be applied after my/other-file.patch": {
          "source": "my/file.patch",
          "after": "other-file"
        },
        "some change to another targeted package": {
          "source": "my/other-file.patch"
        }
      }    
    }
  }
}

```

Multiple dependencies can be defined when after/before value given as an array.

## Patches: version restriction

There are several ways a version restriction for a patch can be defined, the choice on which one to use 
usually depends on a situation and how much extra information needs to be configured for the patch to 
apply correctly. 

```json
{
  "extra": {
    "patches": {
      "targeted/package": {
        "applies when targeted/package version is less than 1.2.3)": {
          "<1.2.3": "example/some-fix.patch"
        },
        "same as first definition, but enabled more configuration options": {
          "source": "example/some-fix.patch",
          "version": "<1.2.3"
        },
        "applies when other/package's version is >=2.1.7": {
          "source": "example/other-fix.patch",
          "depends": {
            "other/package": ">=2.1.7",
            "php": ">=7.1.0"
          }
        }
      }
    }
  }
}
```

It's also possible to make all defined patches to depend on certain package as well by defining a following
key under 'extras'. This is useful in projects where most of the targeted packages are strictly pulled in by 
same meta-package (as is the case with Magento2 for example), one can force all the dependency versions to be 
compared against that specific meta package.

```json
{
  "extra": {
    "patches-depend": "some/package"
  }
}
```

When it's defined, all versions defined in patch definition will target that package instead of targeting
the package that the patch is for. This is useful in cases where most of the project's modules are pulled
in by one single package. This setting will only affect patches within same composer.json

It's also possible to branch this configuration when value is provided as an array

```json
{
  "extra": {
    "patches-depend": {
        "default": "some/package",
        "*": "some/meta-package",
        "some/widget-*": "some/core-dependency"
    }
  }
}
``` 

Note that 'default' and '*' are reserved for internal use where 'default' will be default fallback and
'*' refers to bundled patches.

## Patches: version branching

When there are almost identical patches for different version of some package, then they can be declared
under same `label` or under `source` key depending on how complex rest of the declaration is.

```json
{
  "extra": {
    "patches": {
      "some/package": {
        "having two patches for same fix": {
          ">=1.0.0 <1.2.0": "some/path/legacy.patch",
          ">=1.2.0": "some/path/current.patch"
        }
      },
      "some/other-package": {
        "same done for extended patch declaration format": {
          "source": {
            ">=1.0.0 <1.2.0": "some/path/legacy.patch",
            ">=1.2.0": "some/path/current.patch"
          }
        }
      }
    }
  }
}
```

## Patches: base path variables

Base path variables allow developers to shorten the definition of patch paths which might become cumbersome
and repetitive.

```json
{
  "extra": {
    "patches-base": "patches/{{VendorName}}_{{ModuleName}}/{{version}}/{{file}}",
    "patches": {
      "some/package-name": {
        "Fix: back-port for some important fix": {
          "source": "important-fix.patch",
          "version": [
            ">=v2.7.0 <v2.7.1",
            ">=v2.7.1 <v2.8.33",
            ">=v2.8.33 <v3.0.0"
          ]
        }
      }
    }
  }
}

```

The following will use the version information and targeted package name to generate source paths:

    <owner-root>/patches/Some_PackageName/v2.7.0/important-fix.patch
    <owner-root>/patches/Some_PackageName/v2.7.1/important-fix.patch
    <owner-root>/patches/Some_PackageName/v2.8.33/important-fix.patch

The variables can also have partial value strip rules to shorten the names.

```json
{
  "extra": {
    "patches-base": "patches/{{VendorName}}_{{(Package|Other)ModuleName}}/{{version}}/{{file}}",
    "patches": {
      "some/package-name": {
        "Fix: back-port for some important fix": {
          "source": "important-fix.patch",
          "version": [
            ">=v2.7.0 <v2.7.1"
          ]
        }
      }
    }
  }
}

```

The following little change will result the patches to be taken from following paths

    <owner-root>/patches/Some_Name/v2.7.0/important-fix.patch

These rules will apply in the boundaries of the composer.json where the base path rule was defined. 

If version is not known (not defined as a restriction), but is used in patches-base definition, then the
value "0.0.0" will be used. 

In case patch comment is just a repetition of what the path file name says, the following can be used:

```json
{
  "extra": {
    "patches-base": "patches/{{VendorName}}_{{(Package|Other)ModuleName}}/{{version}}/{{(feature|fix)label}}",
    "patches": {
      "some/package-name": {
        "Fix: other-fix.patch": "1.2.3",
        "Fix: back-port-for-some-important-fix.patch": [
            ">=2.7.0 <2.7.1",
            ">=2.8.33 <3.0.0"
        ]
      }
    }
  }
}

```

The following little change will result the patches to be taken from following paths

    <owner-root>/patches/Some_Name/1.2.3/other-fix.patch
    <owner-root>/patches/Some_Name/2.7.0/back-port-for-some-important-fix.patch
    <owner-root>/patches/Some_Name/2.8.33/back-port-for-some-important-fix.patch

Note the value-strip rules that have been defined for label which take care of not including "Fix: " prefix
when using label as filename. 

## Patches: bundled patches

In case there's a need to define a patch that targets multiple packages within a single patch file, 
alternative patch definition format is recommended:

```json
{
  "extra": {
    "patches": {  
      "*": {
        "fixes for multiple packages (packages explicitly mentioned)": {
          "source": "example/bundled-fixes.patch",
          "targets": [
            "some/module",
            "other/module"
          ]
        },
        "same as above, but targets are auto-resolved from file contents": {
          "source": "example/bundled-fixes.patch"
        }
      }
    }
  }
}
```

Where the `example/bundle.patch` content would have file paths defined in following manner:

```diff
--- some/module/Models/Example.php.org
+++ some/module/Models/Example.php

@@ -31,7 +31,7 @@
      */
     protected function someFunction($someArg)
     {
-        $var1 = 123;
+        $var1 = 456;
         /**
          * rest of the logic of the function
          */
--- other/module/Logic.php.org
+++ other/module/Logic.php

@@ -67,7 +67,7 @@
      */
     protected function otherFunction()
     {
-        $label = 'old';
+        $label = 'new';
         /**
          * some implementation
          */
```

Note that if you plan to use bundled patches whilst also using patches-base, the following approach could
be used:

```json
{
  "extra": {
    "patches-base": {
      "default": "patches/{{VendorName}}_{{(Magento2|Module)ModuleName}}/{{file}}/version-{{version}}.patch",
      "*": "patches/Bundled/{{file}}/version-{{version}}.patch"
    }
  }
}
```

The first dependency version will be used for the bundled patches {{version}} value.

Note that using bundled patches may cause massive re-applying of patches for certain modules when they 
change due to the architecture of this plugin, which relies on re-installing the targeted packages to
avoid potential errors that might be caused by the package's code being in an unexpected/tampered state. 

## Patches: defining patches with strict path strip level

By default, the patcher will try to apply the patch with several path stripping options - in some cases 
this is not something that one wants to allow - for example: if the patch is in full extent just creating 
new files, it might end up creating them to wrong directories. In some cases, some patches might have 
unconventional path definitions that derive from other project patches. Rather than changing the global
settings, it's possible to define custom ones for just one patch.

```json
{
  "extra": {
    "patches": {  
      "targeted/package": {
        "Some patch description": {
          "source": "example.patch",
          "level": "0"
        }
      }    
    }
  }
}
```

## Patches: local patch

In case you would like a patch only to apply when working with some package directly (where the package 
itself is ROOT package), a special flag can be defined to indicate such a thing.

```json
{
  "extra": {
    "patches": {  
      "targeted/package": {
        "Some patch description": {
          "source": "example.patch",
          "local": true
        }
      }    
    }
  }
}
```

Note that this is somewhat conflicting on how Composer deals with this where the case with composer is that 
when you declare something as require-dev, it'll only get installed when the package is ROOT.

In the context of this plugin, this flag will allow similar situation to be achieved:

* When installing the owner of the patch as a dependency: patch is not applied
* When cloning the owner separately and running composer install: patch will be applied

## Patches: skipping patches

In case there's a need to temporarily skip patches which is usually the case when going through
maintenance or upgrade of the underlying project's framework, a skip flag can be used to pass 
over certain declaration lines.

```json
{
  "extra": {
    "patches": {  
      "targeted/package": {
        "This patch will be ignored": "example.patch#skip"
      }    
    }
  }
}
```

Note that in case patches-base is used, the #skip flag will be naturally be added to the end
of the resolve patch path.

## Patches: patch applier cwd options

In cases where there's a need to apply a patch on a file that is mapped to the project root or vendor bin
root, a patch applier working directory resolver mode can be defined (which technically would mean that the 
applier works off of custom root/directory when applying certain patch):

```json
  {
    "extra": {
      "patches": {  
        "targeted/package": {
          "This patch downloads a file to project root": {
            "source": "example.patch",
            "cwd": "project"
          }
        }    
      }
    }
  }
```

This will tell the patch applier to: (a) reset targeted/package; (b) apply 'example.patch' in project root. 
That means that the targeted file paths within example.patch should also target files relative to project root. 

Available cwd options:

1. **install** - (default) uses package install folder 
2. **vendor** - uses vendor path
3. **project** - uses project path
4. **autoload** - uses path that the package has configured as autoloader root (will use first path when multiple
   paths defined). Falls back to using 'install' path when autoloader config not found.

This is particularly useful when `targeted/package` introduces it's own file mapper mechanism that is triggered 
by composer events (which might mean that files are copied away from the re-install package before patch applier kicks in).

## Patches: shared config

In case all patches from certain owner package (the package where the patches originate from) require same
setup for every one of them - a shared configuration could be defined (which will be used for all the patches).

```json
  {
    "extra": {
      "patches": {  
        "_config": {
          "cwd": "autoload"
        },
        "targeted/package": {
          "_config": {
            "version": ">2.0.0"
          },
          "This patch downloads a file to project root": "example.patch",
          "More information about the patch": "another.patch"
        }    
      }
    }
  }
```  

This particular example will allow you to skip from having to define the exact same thing for all the patches
that target **targeted/package** and just define it once under the key **_config**. The information put there
will be made available for all the patches.

Note that different config items will be merged (lower scope overwriting the higher) so that what actually 
applies in this case for all the patches is:

```json
{
  "cwd": "autoload",
  "version": ">2.0.0"
}
```

If you have patches tucked away under different patches definitions files, then the config will not be 
shared between them (will apply only to the patches in that particular patches definition file). 

## Patches Exclude: configuration

In case some patches that are defined in packages have to be excluded from the project (project has 
custom versions of the files, conflicts with other patches, etc), exclusions records can be defined 
in the project's composer.json:

```json
{
  "extra": {
    "patches-exclude": {  
      "patch/owner": [
        "some/path/example.patch",
        "example.patch",
        "example",
        "ex*ple"
      ]
    }
  }
}
```
 
Note that all of the exclusion listed above are valid ways of excluding patches.

Will exclude the a patch that was defined in a package in following (or similar) manner ...

```json
{
  "name": "patch/owner",
  "extra": {
    "patches": {
      "targeted/package": {
        "fix description": "some/path/example.patch"
      }
    }
  }
}
```

The important part to note here is to remember that exclusion ignores patch target and focuses on the owner
instead.

## Patcher: configuration

In case it's needed for the patcher to apply the patches using some third-party application or to include
some extra options, it's possible to declare new patcher commands or override the existing ones by adding 
a new section to the "extra" of the composer.json of the project. Note that this example is a direct copy 
of what is built into the plugin. Changes to existing definitions are applied recursively.

_Note that by default, user does not really have to declare any of this, but everything can be overridden._ 

```json
{
  "extra": {
    "patcher": {
      "search": "patches",
      "search-dev": "patches-dev",
      "file": "patches.json",
      "file-dev": "development.json",
      "ignore": ["node_modules"],
      "depends": {
        "*": "magento/magetno2-base"
      },
      "paths": {
        "*": "src/Bundled/{{file}}/version-{{version}}.patch"
      },
      "graceful": false,
      "force-reset": false,
      "secure-http": true,
      "sources": {
        "project": true,
        "packages": true,
        "vendors": true
      },
      "appliers": {
        "DEFAULT": {
          "resolver": {
            "default": "< which",
            "windows": "< where"
          }
        },
        "GIT": {
          "ping": "!cd .. && [[bin]] rev-parse --is-inside-work-tree",
          "bin": "[[resolver]] git",
          "check": "[[bin]] apply -p{{level}} --check {{file}}",
          "patch": "[[bin]] apply -p{{level}} {{file}}"
        },
        "PATCH": {
          "bin": ["[[resolver]] custom-patcher", "[[resolver]] patch"],
          "check": "[[bin]] -t --verbose -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}",
          "patch": "[[bin]] -t -p{{level}} --no-backup-if-mismatch < {{file}}"
        }
      },
      "operations": {
        "ping": "Usability test",
        "bin": "Availability test",
        "check": "Patch validation",
        "patch": "Patching"
      },
      "operation-failures": {
        "check": {
          "garbage": "/(\\n|^)Hmm\\.\\.\\.  Ignoring the trailing garbage/"
        }
      },
      "sequence": {
        "appliers": ["PATCH", "GIT"],
        "operations": ["resolver", "bin", "ping", "check", "patch"]
      },
      "levels": [0, 1, 2]    
    }
  }
}
```

Some things to point out on patcher configuration:
 
1. The options search, search-dev, file, file-dev, depends and paths can be declared on main level 
   of 'extra' config as well, but users are encouraged to keep every configuration option of the module
   in under one key that would allow the 'extra' not to be littered with multiple configuration key options. 
2. Sequence dictates everything. If applier code or operation is not mentioned in sequence configuration, 
   it's not going to be taken into account. This means that users can easily override the whole standard
   configuration.
3. Multiple alternative commands can be defined for each operation. Operation itself is considered to be 
   success when at least one command call results in a SUCCESS return code 
4. Patch is considered to be applied when all operations can be completed with SUCCESS return code.
5. Exclamation mark in the beginning of an operation will be translated as 'failure is expected'.
6. The values of 'level', 'file' and 'cwd' variables are populated by the plugin, rest of the variables 
   get their value from the response of the operations that have already been processed. This means 
   that 'bin' value will be the result of 'bin' operation. Note that if sequence places 'bin' after 'check' 
   or 'patch', then the former will be just removed from the template.
7. The [[]] will indicate the value is used as-is, {{}} will make the value be shell-escaped.
8. The remote patches are downloaded with same configuration as Composer packages, in case some patches are 
   served over HTTPS, developer can change the 'secure-http' key under patcher configuration to false. This
   will NOT affect the configuration of the package downloader (which has similar setting for package downloader).
9. By default, the patcher will halt when encountering a package that has local changes to avoid developer
   losing their work by accident. the 'force-reset' flag will force the patcher to continue resetting the 
   package code even when there are changes.
10. Setting 'graceful' to true will force the module to continue to apply patches even when some of them 
   fail to apply. By default, the module will halt of first failure.
11. The key 'operation-failures' provides developer an opportunity to fail an operation based on custom 
   output assessment (even when the original command returns with an exit code that seems to indicate that 
   the execution was successful). Operation failures are defined separately for each operation and can be
   customised in root package configuration;
12. In case your package includes other patches other than just the ones that are applied with this plugin, consider
    using patcher/ignore to exclude those the folders that contain such patches. Otherwise false failures will
    be encountered when running `patch:validate`.

Appliers are executed in the sequence dictated by sequence where several path levels are used with 
validation until validation success is hit. Note that each applier will be visited before moving on to 
next path strip level, which result in sequence similar to this:

    PATCH:0 GIT:0 PATCH:1 GIT:1 PATCH:2 GIT:2 ...

## Patcher: sources

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
        "vendors": [
          "vaimo", 
          "magento"
        ]
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
        "packages": [
          "vaimo/patches-*", 
          "!some/ignored-package"
        ]
      }       
    }
  } 
}
```

_These flags do not affect the way 'patch' command works, which will apply patches even when patching has
been said to be disabled in composer.json; These flags indicate whether the patches will be applied on 
'install' and 'update' calls_ 

## Patcher: OS overrides

Achieved by prefixing the patcher config key with general operation-system name.
 
```json
{
   "extra": {
     "patcher": {},
     "patcher-windows": {},
     "patcher-linux": {},
     "patcher-mac": {},
     "patcher-sun": {},
     "patcher-bsd": {},
     "patcher-cygwin": {}
   }
}
```

The contents of each of these keys follows the same structure as described in `Patcher: configuration` and
will be merged into the default configuration (or into configuration overrides that are defined under
the general `patcher` key).

Patches can also be just defined for a certain OS family.

```json
{
   "extra": {
     "patcher-unix": {},
     "patcher-windows": {},
     "patcher-windows-unix": {}   
   }
}
```

## Patches: patch declaration with embedded target information

There's a way of declaring a patch for a project by not writing anything into a json file. This can be
done by using embedded patch meta-data that is based on the following tags:

```diff
This patch fixes a huge issue that made N crash while Y was running.
The description here can be multiple lines which will all be presented
to the user when patch is being applied.

@package <value> (required) indicate which package to target (example: some/package-name)
@label <value> (optional) overrides the description above when provided. Otherwise above info used
@ticket <value> (optional) reference to some issue ID that relates to this fix (added to label)
@link <value> (optional) url to additional data about this patch (added to label)
@depends <value> (optional) other/package (make version constraint target another package instead) 
@version <value> (optional) >=1.1.0 <1.4.0
@after <value> (optional) Used in case a patch should be applied after another branch
@before <value> (optional) Used in case a patch should be applied before another branch
@skip (optional) If this tag is present, then the patch will not be applied
@cwd <value> (optional) Specify in which root path the patch is applied (install, vendor, project, autoload)
@type <value> (optional) Options: dev, bundle, local (Using multiple: dev+bundle)
@level <value> (optional) 0 (forces/locks the patch applier to use certain patch path strip level)
@category <value> (optional) free-form value to explain what the patch is (fix/feature/enhancement)

--- Models/Example.php.org
+++ Models/Example.php
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

When a patch is declared like this, no additional information is needed to be provided to the plugin. Of
those tags, the mandatory ones are:

```
@package - targeted package
@type bundle - as an alternative to @package when dealing with patch that targets multiple packages
```
 
Alternatively package and version can be declared together with a one-liner

```
@version some/package-name:>=1.1.0 <1.4.0 
```

The only extra thing that needs to be provided by the patch owner package is a flag that will allow the 
patches to be searched for from the root of the owner.

```json
{
  "extra": {
    "patches-search": "patches"
  }
}
```

Note that "search" can point to the same folder where you have the patches that have a proper declaration
in a JSON file. 

Multiple values can be also used in the declaration by providing them as array of strings. 

The key "patches-base", etc are not mandatory to be declared when using patches-search as the exact path
of the patches will already be known.

## Patch Commands

Installing the plugin will introduce a new composer command group: **patch**

```shell
# Apply new patches (similar to patch apply on 'composer install') 
composer patch:apply

# Apply new patches and continue even if some of them fail
composer patch:apply --graceful

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

# Reset patches for specified packages (path:apply will restore them)
composer patch:undo my/package other/package

# Reset one specific patch on package
composer patch:undo --filter some-fix my/package

# Reset packages that have patch that contains 'some-fix' in it's path definition
composer patch:undo --filter some-fix

# Gather patches information from /vendor instead of install.json and apply
composer patch:apply --from-source

# Ideal for testing out a newly added patch against my/package
composer patch:redo --from-source

# Check that all patches have valid target package info (using info from vendor)
composer patch:validate --from-source

# Check that patches that are owner by ROOT package are all defined correctly
composer patch:validate --local

# List registered with their current status (with optional filter(s))
composer patch:list --status new --status removed

# List all patches that have either changed, are new or got removed 
composer patch:list --status '!applied'
```

Note that `--from-source` is only needed when patch target definitions are provided within a 
composer.json file. If patches Provided in a separate file, the only reason to use this flag
is when `patches-file` definitions have changed.

The main purpose of this command is to make the maintenance of already created patches and adding new 
ones as easy as possible by allowing user to test out a patch directly right after defining it without 
having to trigger 'composer update' or 'composer install'.

Note that 'validate' command will also validate patches with constraints or with #skip flag in their
path (or in embedded data).

## Environment Variables

* **COMPOSER_PATCHES_GRACEFUL** - continue applying patches even if some of them fail (default behaviour
  is to exit on first failure).
* **COMPOSER_PATCHES_SKIP_CLEANUP** - Will leave packages patched even when vaimo/composer-patches is 
  removed. By default, patched packages are re-installed to reset the patches (useful when creating 
  immutable build artifacts without any unnecessary modules installed).
* **COMPOSER_PATCHES_FORCE_RESET** - Allows patcher to reset patch-targeted packages that have local 
  changes. Default behaviour will lead to the process to be halted to avoid developers from losing their
  work.

## Upgrading The Module

When upgrading the module, one might encounter odd crashes about classes not being found or class 
constructor arguments being wrong. 

This usually means that the class structure or constructor footprint in some of the classes have changed 
after the upgrade which means that the plugin might be running with some classes from the old and some 
classes from the new version. 

Due to the fact that the patcher kicks in very late in the process of installing a project (before 
auto-loader generation), developers are advised to re-execute 'composer install'.

## Example: defining a patch in a dependency package that targets another package

Let's say that you have a root composer.json which requires two packages: A and B. The package B depends 
on (requires) package A. In order to be able to use package B properly, package A needs to be patched.

So in this case package B would have the following in the composer.json:

```json
{
    "extra": {
        "patches": {
            "A": {
                "Compatibility magic": "patches/compatibility-with-package-a.patch"
            }
        }
    }
}
```

... where the patch is placed to `<package B root>/patches/compatibility-with-dependency-a.patch` and paths 
withing the patch file are relative to the root of package A.

This can be tested out with...

```shell
composer patch:redo --from-source --filter compatibility-with-pack
```

_The 'filter' part is really optional as you could also try to re-apply everything. the 'from-source' 
makes the patches scan for patches directly in 'vendor' folder (which allows patches to be pre-tested 
before [updating/committing changes to] a given package). The default behaviour is to scan for them 
in installed.json_

## Development

The module ships with several utility scripts that either deal with static code analysis or with running 
the tests. Note that all these commands expect user to have installed all the dependencies of the package
beforehand.

```
# Runs one or several static code analysis tools against the code-base
composer code:analyse

# Fixes as many issues as possible automatically (phpcs auto-fixer)
composer code:normalise

# Runs several integration-tests against the application
composer code:test

# Runs just one scenario for all test installations
composer code:test sequence

# Runs just one scenario for just one installation
composer code:test using-file:skipped-patch

# Validate that all production-level dependencies are compatible 
# with the system requirements of this package 
composer code:deps
```

### Components

The tests have the following components:

* **installations** - full composer project that the scenarios will be executed against. Installations differ in
  their patcher configurations, but should end up in same state when a scenario is executed (except in some
  cases where certain configuration can not be used. Example: using patches-search to apply remote patch).  
* **modules** - the modules that are included in the installations. All assertions are based on checking the
  state of the module files after patches have been applied.
* **scenarios** - includes all the possible scenarios (with different patches) that will be executed against 
  each installation.

### Adding new scenarios

When new scenarios are introduced, one can just create new folder under test/scenarios and define the 
patches that use certain features or expose certain issue. Note that the scenario will be executed with
commands provided in `.commands` and will use the description provided under `.label`.

Note that assertions are taken from the patch files, where they should be defined using following
convention/template:

```
@assert <package-name>,<before>,<after>
```
