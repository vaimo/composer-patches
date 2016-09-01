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
    "cweagans/composer-patches": "~1.0",
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
    "cweagans/composer-patches": "~1.0",
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
    "cweagans/composer-patches": "~1.0",
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

## Alternative format

In case it's important to retain the patching order, one can also use alternative declaration format that uses array wrapper:

```
{
  "extra": {
    "patches": {
      "targeted/package": [
        {
          "label": "desription about my patch", 
          "url": "my/file.patch"
        }
      ]
    }
  }
}

```

## Dependency on certain packagess

Using the extended formats opens up some possibilities to apply a patch only when certain 3rd party packages are available.

```
{
  "extra": {
    "patches": {
      "targeted/package": [
        {
          "label": "desription about my patch", 
          "url": "my/file.patch",
          "require": {
            "some/package": "1.2.3"
          }
        }
      ]
    }
  }
}
```

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

## Excluding patches

In case some patches that are defined in packages have to be excluded from the project (project has custom verisons of the files, conflicts with other patches, etc), patch exclusions can be defined.

Package that owns the patch:

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

Project level:

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

## Hints on creating a patch 

The file-paths in the patch should be relative to the targeted package root and start with ./

This means that patch for a file <projet>/vendor/my/package/some/file.php whould be targeted in the patch as ./some/file.php

## Difference between this and netresearch/composer-patches-plugin

* Works on project AND package level
* This plugin is much more simple to use and maintain
* This plugin doesn't require you to specify which package version you're patching
* This plugin is easy to use with Drupal modules (which don't use semantic versioning).
* This plugin will gather patches from all dependencies and apply them as if they were in the root composer.json

## Credits

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex and difficult to use.
