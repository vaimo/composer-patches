# Error Handling

This document covers the different scenarios (and how to proceed) where patch applying results in an error.

The standard applier output will propose the most probable reason for a patch to fail, but user can always
peek into the full applier log by running the whole process with increased verbosity.

The failures usually output a hint that guides the developer to do so as well.

```txt
(For full, unfiltered details please use: composer patch:apply -vvv)
```

## Context Failure

The most typical reason for a patch not to apply where the targeted content footprint can not be matches
within the file.

```txt
- Applying patches for some/package (2)
  ~ patch/owner: patches/important-fix.patch [NEW]
    Introduces important change to the core logic of 
    event queue processing
    Probable causes for the failure:

    > PATCH
      @ src/some-file.php
      ! Hunk #1 FAILED at 5.
```

This usually means that the're are either other patches that change the file in the same region 
(conflicting patches) or that the patch itself includes information that is no longer valid (outdated patch).

* **conflicting patches** - patches have to either merged (if they address somewhat similar issue) or sequenced.

* **outdated patch** - the changes from the patch have to be re-applied manually and new patch created 
  afterwards (or the whole patch - or parts of it - should be dropped if the changes are no longer relevant).    

## Output Analysis

The module ships with a feature that allows certain successful patches to be declared as 'failures'. The
reasons for this come from the fact that the patch applier applications might behave differently of types
of OS which might result in some patches either being partially applied without proper exit code from the 
application or being reverted.

```txt
- Applying patches for some/package (2)
  ~ patch/owner: patches/important-fix.patch [NEW]
    Introduces important change to the core logic of 
    event queue processing
    Probable causes for the failure:                                    
                                                                               
    > PATCH                                                             
      @ Model/Customer.php          
      ! Success changed to FAILURE due to output analysis (garbage):
        Hmm...  Ignoring the trailing garbage
```

Such messages mean that the patch applier actually said that everything was OK, but something suspicious
was picked up from the log of said applier.

The important part in this situation is the 'garbage' reference which relates to values recorded in the
configuration of this plugin where certain output patterns have been blacklisted. See the examples to 
this [HERE](./CONFIGURATION.md#structure) (look for the "operation-failures" section).

* **garbage** - patch application deems certain parts of the patch as junk that could be ignored. Although
  mostly harmless, there are releases of the 'patch' binary itself where totally valid parts of the patch
  end up being incorrectly labeled as such. Usually encountered with patches that have several changes done
  to different parts of the file (where the N+1th change is ignored). The fix to this particular case, 
  interestingly enough is quite often relatively easy: just add extra BLANK line to the end of the patch.   
    
* **reversals** - the code that the patch includes is already in place. Either there is another patch applied
  with exact same content or the package itself has been updated to a version that includes the fix by default.
  