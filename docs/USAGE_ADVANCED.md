# Advanced Usage

Detailed guide on how to use the advanced configuration options of the plugin to define patches. 

## Bundles

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

## Sequenced Patches

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

## Platform Version Restriction

Patches can be defined to be only applied when certain platform constraint requirements are met.

```json
{
  "extra": {
    "patches": {
      "targeted/package": {
        "applies only when running on system that has runs PHP with high enough version": {
          "source": "example/other-fix.patch",
          "depends": {
            "php": ">=7.1.0"
          }
        }
      }
    }
  }
}
```

## Base Path Variables

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

## Strict Path Strip Level

By default, the patcher will try to apply the patch with several path stripping options - in some cases 
this is not something that one wants to allow - for example: if the patch is in full extent just creating 
new files, it might end up creating them to wrong directories. 

In some cases, some patches might have unconventional path definitions that derive from other project 
patches. Rather than changing the global settings, it's possible to define custom ones for just one patch.

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

## Local Patch

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

## Skipping Patches

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

## Applier Cwd Options

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

This is particularly useful when `targeted/package` introduces it's own file mapper mechanism that is 
triggered by composer events (which might mean that files are copied away from the re-install package before 
patch applier kicks in).

## Shared Config

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

## Exclusion

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

## Comments

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
