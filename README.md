# Vaimo Composer Patches

[![Latest Stable Version](https://poser.pugx.org/vaimo/composer-patches/v/stable)](https://packagist.org/packages/vaimo/composer-patches)
[![Build Status](https://travis-ci.org/vaimo/composer-patches.svg?branch=release/3)](https://travis-ci.org/vaimo/composer-patches)
[![Total Downloads](https://poser.pugx.org/vaimo/composer-patches/downloads)](https://packagist.org/packages/vaimo/composer-patches)
[![Daily Downloads](https://poser.pugx.org/vaimo/composer-patches/d/daily)](https://packagist.org/packages/vaimo/composer-patches)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/vaimo/composer-patches.svg)](https://php.net/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vaimo/composer-patches/badges/quality-score.png?b=release/3)](https://scrutinizer-ci.com/g/vaimo/composer-patches/?branch=release/3)
[![License](https://poser.pugx.org/vaimo/composer-patches/license)](https://packagist.org/packages/vaimo/composer-patches)

Applies a patch from a local or remote file to any package that is part of a given composer 
project. Patches can be defined both on project and on package level.

The way the patches are applied (the commands, pre-checks) by the plugin is fully configurable from 
the composer.json of the project.

Note that the plugin is kept on very old PHP version as legacy software is usually the most common context
where patches are needed.

## Full Documentation

* [Basic Usage](./docs/PATCHES_BASIC.md) - Defining patches via configuration files or embedded metadata  
* [Advanced Usage](./docs/PATCHES.md) - Advanced configuration options
* [Commands](./docs/DEVELOPMENT.md) - Details on the CLI commands that ship with the plugin
* [Configuration](./docs/PATCHER.md) - Advanced configuration options for the patch applier
* [Development](./docs/DEVELOPMENT.md) - Details on the development workflow of the plugin
* [Examples](./docs/EXAMPLES.md) - Examples on usage o the plugin
* [Changes](./CHANGELOG.md) - Changelog per plugin release

## Quick Start

Composer packages can be targeted with patches in two ways: 

* Embedded metadata
* JSON declaration and embedded (recommended for remote patches)

### Patch: Embedded Metadata

```json
{
  "require": {
    "some/package-name": "1.2.3"
  },
  "extra": {
    "patcher": {
      "search": "patches"
    }
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

Full list of tag options (that cover all features of the plugin can be 
found [HERE](#patches-patch-declaration-with-embedded-target-information))

### Patch: JSON Declaration

```json
{
  "require": {
    "some/package-name": "1.2.3"
  },
  "extra": {
    "patches": {
      "some/package-name": {
        "This patch changes ... absolutely everything": "patches/changes.patch",
        "remote patch": "http://www.example.com/remote-patch.patch",
        "remote patch with checksum check": {
          "source": "http://www.example.com/other-patch.patch",
          "sha1": "5a52eeee822c068ea19f0f56c7518d8a05aef16e"
        }
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

### Applier configuration

The way patches are applied can be manipulated by changing the configuration of the patcher or by introducing
additional patch appliers.

In most cases there should not be much of a need to reconfigure the module as it does ship with reasonable
defaults. The appliers supported by default: patch, git.

More information on said topic can be found [HERE](./docs/PATCHER.md).

## Upgrading The Module

When upgrading the module, one might encounter odd crashes about classes not being found or class 
constructor arguments being wrong. 

This usually means that the class structure or constructor footprint in some of the classes have changed 
after the upgrade which means that the plugin might be running with some classes from the old and some 
classes from the new version. 

Due to the fact that the patcher kicks in very late in the process of installing a project (before 
auto-loader generation), developers are advised to re-execute 'composer install'.


