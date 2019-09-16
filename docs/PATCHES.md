# Configuration: Patches

Detailed guide on how to use the advanced configuration options of the plugin to define patches. 

## Configuration: overview

Patches are declared under the following keys in composer.json of the patch owner (may it be project or
a package).

```json
{
  "extra": {
    "patches": {},
    "patches-file": [],
    "patches-search": []
  }
}
```

Where the different groups have the following meaning:

* **patches** - allows patches to be defined in same file.
* **patches-file** - allows patches to be stored in another file. Can be a single file or list of files.
* **patches-search** - scans for patch files in defined directory, relies on embedded target info 
  within the patch (>=3.28.0). Can be a single file or list of files.

The patches module mimics the way composer separates development packages from normal requirements by 
introducing two extra keys, where exact same rules apply as for normal patch declarations.

```json
{
  "extra": {
    "patches-dev": {},
    "patches-file-dev": [],
    "patches-search": []
  }
}
```

The patches declared under those keys will NOT be applied when installing the project with `--no-dev` option.

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
        "example remote patch": "http://www.example.com/remote-patch.patch",
        "example remote patch with checksum check": {
          "source": "http://www.example.com/remote-patch.patch",
          "sha1": "5a52eeee822c068ea19f0f56c7518d8a05aef16e"
        }
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

## Basic Usage: configuring a separate patches file

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
root, a custom applier patch target installation path resolver mode can be defined:

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