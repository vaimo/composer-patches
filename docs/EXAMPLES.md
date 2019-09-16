# Examples

Examples on advanced usage of the module.

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
composer patch --redo --from-source --filter compatibility-with-pack
```

_The 'filter' part is really optional as you could also try to re-apply everything. the 'from-source' 
makes the patches scan for patches directly in 'vendor' folder (which allows patches to be pre-tested 
before updating/committing changes to a given package). The default behaviour is to scan for them 
in installed.json_
