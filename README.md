# composer-patches

Simple patches plugin for Composer. Applies a patch from a local or remote file to any package required with composer.

## Enabling patching for a project

Patching is enabled when:

* project has "patches" key defined under "extra" 
* project has "enable-patching" key defined under "extra" 

_Note that the latter is only useful if you have no patches defined directly on the root level_

```
{
  "extra": {
    "patches": {},
    "enable-patching": true
  }
}

```

## Usage

Example composer.json:

```
{
  "require": {
    "vaimo/composer-patches": "^2.1.0",
    "drupal/drupal": "8.0.*@dev"
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches": {
      "drupal/drupal": {
        "Add startup configuration for PHP server": "https://www.drupal.org/files/issues/add_a_startup-1543858-30.patch"
      }
    }
  }
}

```

## Using an external patch file

Instead of a patches key in your root composer.json, use a patches-file key.

```
{
  "require": {
    "vaimo/composer-patches": "^2.1.0",
    "drupal/drupal": "8.0.*@dev"
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches-file": "local/path/to/your/composer.patches.json"
  }
}

```

## Using patch file

Same format is used for both project (root level scope) patches and for package patches.

```
{
  "require": {
    "vaimo/composer-patches": "^2.1.0",
    "drupal/drupal": "8.0.*@dev"
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches": {
      "targeted/package": {
        "desription about my patch": "my/file.patch"
      }
    }
  }
}

```

Please note that in both cases the patch path should be relative to the context where it's defined:

* For project, it should be relative to project root
* For package, it should be relative to package root 

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

Please note that patch version constraint supports all version definition patterns supported by the version
of Composer.

Same "version" key can be used with alternative definition format as well.

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

## Sequencing patches

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

* Works on project AND package level
* This plugin is much more simple to use and maintain
* This plugin doesn't require you to specify which package version you're patching (but you'll still have the option to do so).
* This plugin is easy to use with Drupal modules (which don't use semantic versioning).
* This plugin will gather patches from all dependencies and apply them as if they were in the root composer.json

## Credits

Heavily modified version of https://github.com/cweagans/composer-patches

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is
abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex
and difficult to use.
