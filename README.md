# composer-patches

Applies a patch from a local or remote file to any package that is part of a given composer 
project. Packages can be defined both on project and on package level.

The way the patches are applied (the commands, pre-checks) by the plugin is fully configurable from 
the composer.json of the project.

[![GitHub release](https://img.shields.io/github/release/vaimo/composer-patches.svg)](https://github.com/vaimo/composer-patches/releases/latest)
[![Total Downloads](https://img.shields.io/packagist/dt/vaimo/composer-patches.svg)](https://packagist.org/packages/vaimo/composer-patches)
[![Daily Downloads](https://img.shields.io/packagist/dd/vaimo/composer-patches.svg)](https://packagist.org/packages/vaimo/composer-patches)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/vaimo/composer-patches.svg)](https://php.net/)
[![License](https://img.shields.io/github/license/vaimo/composer-patches.svg)](https://github.com/vaimo/composer-patches/blob/master/LICENSE_VAIMO.txt)

## Configuration: hardpoints

Patches are declared under the following keys in composer.json of the patch owner (may it be project or
a package).

```json
{
  "extra": {
    "patches": {},
    "patches-file": {}
  }
}
```

The patches module mimics the way composer separates development packages from normal requirements by 
introducing two extra keys, where exact same rules apply as for normal patch declarations: `patches-dev`, 
`patches-file-dev`. 

The patches declared under those keys will NOT be applied when installing the project with `--no-dev` option.
  
## Basic Usage: configuring a patch

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
        "example local patch": "path/to/patches/fix.patch",
        "example remote patch": "http://www.example.com/patch.patch"
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

## Basic Usage: configuring a patches file

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

## Basic Usage: patch file format

Patches are applied relative to the root of the composer package that the patch is targeting: the file 
paths in the patch should reflect that.

So if the patch is defined for my/package and my/package has a file vendor/my/package/Models/Example.php,
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

There are several ways a version restriction for a patch can be defined, the choice on which one to use usually depends on a situation and how much extra information needs to be configured for the patch to apply correctly. 

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
            "other/package": ">=2.1.7"
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
--- some/module/Models/Example.php.org	2017-05-24 14:13:36.449522497 +0200
+++ some/module/Models/Example.php	2017-05-24 14:14:06.640560761 +0200

@@ -31,7 +31,7 @@
      */
     protected function someFunction($someArg)
     {
-        $var1 = 123;
+        $var1 = 456;
         /**
          * rest of the logic of the function
          */
--- other/module/Logic.php.org	2017-05-24 14:13:36.449522497 +0200
+++ other/module/Logic.php	2017-05-24 14:14:06.640560761 +0200

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

## Patches: skipping patches

In case there's a need to temporarily fast-exclude patches which is usually the case when going through
maintenance or upgrade of the underlying project's framework, a skip flag can be used to pass over certain 
declaration lines.

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
      "secure-http": true,
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

Some things to point out on patcher configuration:
 
1. Sequence dictates everything. If applier code or operation is not mentioned in sequence configuration, 
   it's not going to be taken into account. This means that users can easily override the whole standard
   configuration.
2. Multiple alternative commands can be defined for each operation. Operation itself is considered to be 
   success when at least one command call results in a SUCCESS return code 
3. Patch is considered to be applied when all operations can be completed with SUCCESS return code.
4. Exclamation mark in the beginning of an operation will be translated as 'failure is expected'.
5. The values of 'level', 'file' and 'cwd' variables are populated by the plugin, rest of the variables 
   get their value from the response of the operations that have already been processed. This means 
   that 'bin' value will be the result of 'bin' operation. Note that if sequence places 'bin' after 'check' 
   or 'patch', then the former will be just removed from the template.
6. The [[]] will indicate the value is used as-is, {{}} will make the value be shell-escaped.
7. The remote patches are downloaded with same configuration as Composer packages, in case some patches are 
   served over HTTP, developer can change the 'secure-http' key under patcher configuration to false. This
   will NOT affect the configuration of the package downloader.

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

## Patch Command

Installing the plugin will introduce a new composer command: **composer patch**

```shell
# Re-apply new patches (similar to patch apply on 'composer install') 
composer patch 

# Re-apply all patches
composer patch --redo 

# Re-apply patches for one speicif package
composer patch --redo my/package 

# Re-apply all patches except patches declared against my/package
composer patch --redo '!my/package'

# Re-apply patches for one specific package with patch name filter 
composer patch --filter wrong-time-format --filter other-file --redo my/package 

# Re-apply patches and skip filenames that contain 'some<anything>description'  
composer patch --filter '!some*description' --redo my/package 

# Reset all patched packages
composer patch --undo 

# Reset one specific patched package
composer patch --undo my/package 

# Reset one specific patch on package
composer patch --undo --filter some-fix my/package

# Reset packages that have patch that contains 'some-fix' in it's path definition
composer patch --undo --filter some-fix

# Gather patches information from /vendor instead of install.json
composer patch --from-source

# Ideal for testing out a newly added patch against my/package
composer patch --from-source --redo my/package
```

The main purpose of this command is to make the maintenance of already created patches and adding new 
ones as easy as possible by allowing user to test out a patch directly right after defining it without 
having to trigger 'composer update' or 'composer install'.

## Environment Variables

* **COMPOSER_PATCHES_FATAL_FAIL** - exit after first patch failure is encountered
* **COMPOSER_PATCHES_SKIP_PACKAGES** - comma-separated package names to exclude from patching, useful 
  when maintaining patches on package upgrade. Does not affect bundled patches.
* **COMPOSER_PATCHES_FROM_SOURCE** - always use data directly from owner's composer.json rather than 
  using the information stored in installed.json
* **COMPOSER_PATCHES_REAPPLY_ALL** - reapply all patches even when previously applied. Re-applies even 
  previously applied patches.
* **COMPOSER_PATCHES_SKIP_CLEANUP** - Will leave packages patched even when vaimo/composer-patches is 
  removed. By default, patched packages are re-installed to reset the patches (useful when creating 
  immutable build artifacts without any unnecessary modules installed).

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

So in this case package B would have the following in it's composer.json
```json
{
    "extra": {
        "patches": {
            "A": {
                "Compatibility: magic": "patches/compatibility-with-package-a.patch"
            }
        }
    }
}
```
... where the patch is placed to <package B root>/patches/compatibility-with-dependency-a.patch and paths withing the patch file are relative to the root of package A.

This can be tested out with...

```shell
composer patch --redo --from-source --filter compatibility-with-dependency-a
```

_The 'filter' part is really optional as you could also try to re-apply everything. the 'from-source' makes the patches scan for patches directly in 'vendor' folder (which allows patches to be pre-tested before [updating/commiting changes to] a given package). The default behaviour is to scan for them in installed.json_

## Changelog 

_Changelog included in the composer.json of the package_
