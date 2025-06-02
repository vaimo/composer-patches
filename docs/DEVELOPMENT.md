# Development

This documentation covers topics like triggering code style checks, running the tests and creating new 
releases.  

## Branching

The current latest MAJOR release is maintained and updated via commits to 'master' branches. 

All older MAJOR releases are maintained through branches that follow the naming convention of `release/<MAJOR>`.

## Workflow

All work on the module should be done (if possible) by checking out the lowest MAJOR release branch,
making the changes there and merging them upwards to newer MAJOR versions. 

Note that when dealing with bug-fixes, developer MUST use the lowest MAJOR version as starting point that 
has said bug/issue rather than just fixing it on the latest line.

## Commands

```shell
# Runs one or several static code analysis tools against the code-base
composer code:lint

# Runs just one type of code linter
composer code:lint phpmd

# Fixes as many issues as possible automatically (phpcs auto-fixer)
composer code:fix

# Runs several integration-tests against the application
composer code:test

# Runs just one scenario (that matches with the filter) for all test installations
composer code:test sequence

# Runs all scenarios on one specific installation
composer code:test root-using-file:

# Runs just one scenario for just one installation
composer code:test root-using-file:apply-single

# Runs just one scenario for just one installation (verbose output)
VERBOSE=1 composer code:test root-using-file:apply-single

# Validate that all production-level dependencies are compatible 
# with the system requirements of this package 
composer code:deps
```

## Testing

The module ships with several utility scripts that either deal with static code analysis or with running 
the tests. Note that all these commands expect user to have installed all the dependencies of the package
beforehand.

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

### Assertions

Note that assertions are taken from the patch files (or defined in `.commands`), where they should be defined 
using following convention/template:

```
@assert <package-name>,<before>,<after>
```

### Directives

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

## Creating A New Release

The module uses semantic versioning which means that developer should choose proper increment to current
version that would reflect the nature of the change. More details about the topic can be found [HERE](https://semver.org).

### Steps

1. Choose proper version that would reflect the contents of the release and update changelog.json; Use branch 
   reference when release not done for the latest MAJOR version; When releasing new MAJOR version, list all
   breaking changes under the topic of "breaking".
2. Tag the release based on the same version that you added to changelog
3. Update changelog output with `composer changelog:generate`
4. Push the changes and the created tag(s).
5. When feature was implemented on older MAJOR version, continue by checking out the higher MAJOR version 
   branch, merge in the changes from lower branch and repeat the steps of this guide.   

## Development Container

The modules ships with a dedicated development branch [devbox](https://github.com/vaimo/composer-changelogs/tree/devbox) 
which contains configuration for spinning up a dedicated development environment that can be used together 
with VSCode's [Remote Containers](https://code.visualstudio.com/docs/remote/containers).

Note that there is no strict requirement to use such a setup, but it's what was used to author the plugin
and if you want to be sure that you have everything up and running without hick-ups, you can just as well
take the shortcut.

System requirements:

1. Have [Docker](https://www.docker.com/) installed
2. Have [Visual Studio Code](https://code.visualstudio.com/) installed 
3. Hasve [Remote Containers](https://code.visualstudio.com/docs/remote/containers) extension installed on VSCode
4. Have [Mutagen](https://mutagen.io) installed (used for selecting syncing)

Setup:

4. `git checkout devbox .devcontainer Dockerfile docker-compose.yml mutagen.yml`
5. `git reset .devcontainer Dockerfile docker-compose.yml mutagen.yml`
6. [open the project with VSCode that has Remote Container extension installed]
7. [use the 'Reopen in Container' option that is given in a prompt that opens]
8. `mutagen project start`
9. `docker-compose exec devbox composer install`

Note this setup does come with a pre-bootstrapped xDebugger, you just have to use the Run menu 
in VSCode and start listening and trigger a command via the terminal.
