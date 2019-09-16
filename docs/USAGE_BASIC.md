# Basic Usage

Detailed guide on how to use the advanced configuration options of the plugin to define patches. 

## Overview

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

## Declaration: composer.json

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

## Declaration: patches.json

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

## Declaration: multiple json files

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

## Patch File Format

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

_Path stripping levels can be defined/modifier/added to allow patches with different relative paths for 
targeted files to also apply._

## Embedded Metadata

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
@cwd <value> (optional) Specify the cwd path for patch applier (install, vendor, project, autoload)
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