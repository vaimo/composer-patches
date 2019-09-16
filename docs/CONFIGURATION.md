# Configuration 

Detailed guide on how to use the advanced configuration options of the plugin to manipulate the way patches
are being applied.

## Overview

The plugin ships with a pre-configured configuration for the patch applier, but changes to said defaults
can be made by defining overrides under the following keys.

```json
{
  "extra": {
    "patcher": {},
    "patcher-<os_type>": {}
  }
}
```

## Structure

In case it is needed for the patcher to apply the patches using some third-party application or to include
some extra options, it's possible to declare new patcher commands or override the existing ones by adding 
a new section to the "extra" of the composer.json of the project. 

Note that this example is a direct copy of what is built into the plugin. Changes to existing definitions 
are applied recursively.

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
          "bin": "which git",
          "check": "[[bin]] apply -p{{level}} --check {{file}}",
          "patch": "[[bin]] apply -p{{level}} {{file}}"
        },
        "PATCH": {
          "bin": ["which custom-patcher", "which patch"],
          "check": {
            "default": "[[bin]] -t --verbose -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}",
            "bsd": "[[bin]] -t -p{{level}} --dry-run < {{file}}"
          },
          "patch": {
            "default": "[[bin]] -t -p{{level}} --no-backup-if-mismatch < {{file}}",
            "bsd": "[[bin]] -t -p{{level}} < {{file}}"
          }
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
        "operations": ["bin", "ping", "check", "patch"],
        "operations:sanity": ["resolver", "bin"]
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
13. Every applier operation can be specified to have a unique command per OS type. If none is specified for 
    specific OS, the default value is used.
14. Sanity operation sequence is used to validate that certain patch applier is available in given system. 
    Appropriate error response is given when none of the specified appliers is available.  

Appliers are executed in the sequence dictated by sequence where several path levels are used with 
validation until validation success is hit. Note that each applier will be visited before moving on to 
next path strip level, which result in sequence similar to this:

    PATCH:0 GIT:0 PATCH:1 GIT:1 PATCH:2 GIT:2 ...

## Disabling patching

In case the functionality of the plugin has to be fully disabled, developer can just set "patcher"
to "false".

```json
{
  "extra": {
    "patcher": false
  }
}
```

## Sources

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

## Overrides Based On OS Type 

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

Note that the OS-based split can also be introduced on `appliers` configuration key where the single value 
can be replaced with a dictionary that features a `default` key and keys per OS type that requires a call
configuration that differs from the default.

```json
{
  "extra": {
    "patcher": {
      "appliers": {
        "PATCH": {
          "check": "[[bin]] -t --verbose -p{{level}} --no-backup-if-mismatch --dry-run < {{file}}",
          "patch": {
            "default": "[[bin]] -t -p{{level}} --no-backup-if-mismatch < {{file}}",
            "bsd": "[[bin]] -t -p{{level}} < {{file}}"
          }
        }
      }  
    }
  }
}
```
