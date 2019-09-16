# Development

The module ships with several utility scripts that either deal with static code analysis or with running 
the tests. Note that all these commands expect user to have installed all the dependencies of the package
beforehand.

```shell
# Runs one or several static code analysis tools against the code-base
composer code:analyse

# Runs just one type of code analyser
composer code:analyse phpmd

# Fixes as many issues as possible automatically (phpcs auto-fixer)
composer code:normalise

# Runs several integration-tests against the application
composer code:test

# Runs just one scenario for all test installations
composer code:test sequence

# Runs all scenarios on one specific installation
composer code:test using-file:.*

# Runs just one scenario for just one installation
composer code:test using-file:skipped-patch

# Validate that all production-level dependencies are compatible 
# with the system requirements of this package 
composer code:deps
```

### Components

The tests have the following components:

* **installations** - full composer project that the scenarios will be executed against. Installations differ in
  their patcher configurations, but should end up in same state when a scenario is executed (except in some
  cases where certain configuration can not be used. Example: using patches-search to apply remote patch).  
* **modules** - the modules that are included in the installations. All assertions are based on checking the
  state of the module files after patches have been applied.
* **scenarios** - includes all the possible scenarios (with different patches) that will be executed against 
  each installation.
* **dependencies** - sandbox for testing the possibility to install the defined dependencies on all supported
  versions of PHP; Said dependencies will also be analysed for syntax compatibility.

### Adding new scenarios

When new scenarios are introduced, one can just create new folder under test/scenarios and define the 
patches that use certain features or expose certain issue. Note that the scenario will be executed with
commands provided in `.commands` and will use the description provided under `.label`.

If the scenario has `.output` file, the whole Composer console output of the scenario will be compared 
against the contents of that file. 

#### Assertions

Note that assertions are taken from the patch files (or defined in `.commands`), where they should be defined 
using following convention/template:

```
@assert <package-name>,<before>,<after>
```

#### Commands

The different commands that one can use within `.commands` file:

```shell
# Calls Composer with mentioned command
patch:apply

# Does a raw system call
>echo test

# Modifies the state of the project to consider there to
# be a need to install the some/package
@queue_install some/package

# Modifies the state of the project to consider there to
# be a need to update the some/package
@queue_update some/package

# Exit the test scenario with a positive return code
@abort

# Assert state for the files: <package-name>,<before>,<after>
# where <before> should not be found in a file and <after> should be
@assert some/package,thisValue,thatValue

# Go through all the assertions defined in patch files
@assert_changes
```
