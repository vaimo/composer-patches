<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvents;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Script\PackageEvent;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Symfony\Component\Process\Process;

class Patches implements PluginInterface, EventSubscriberInterface {

  /**
   * @var Composer $composer
   */
  protected $composer;
  /**
   * @var IOInterface $io
   */
  protected $io;
  /**
   * @var EventDispatcher $eventDispatcher
   */
  protected $eventDispatcher;
  /**
   * @var ProcessExecutor $executor
   */
  protected $executor;
  /**
   * @var array $patches
   */
  protected $patches;
  /**
   * @var array $installedPatches
   */
  protected $installedPatches;
  /**
   * @var array $packagesByName
   */
  protected $packagesByName;

  /**
   * Apply plugin modifications to composer
   *
   * @param Composer    $composer
   * @param IOInterface $io
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->eventDispatcher = $composer->getEventDispatcher();
    $this->executor = new ProcessExecutor($this->io);
    $this->patches = array();
    $this->installedPatches = array();
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return array(
      ScriptEvents::PRE_INSTALL_CMD => "checkPatches",
      ScriptEvents::PRE_UPDATE_CMD => "checkPatches",
      PackageEvents::PRE_PACKAGE_INSTALL => "gatherPatches",
      PackageEvents::PRE_PACKAGE_UPDATE => "gatherPatches",
      ScriptEvents::POST_INSTALL_CMD => "postInstall",
      ScriptEvents::POST_UPDATE_CMD => "postInstall",
    );
  }

  protected function preparePatchDefinitions($patches, $ownerPackage = null) {
    $_patches = array();

    $vendorDir = $this->composer->getConfig()->get('vendor-dir');

    if ($ownerPackage) {
      $manager = $this->composer->getInstallationManager();
      $patchOwnerPath = $manager->getInstaller($ownerPackage->getType())->getInstallPath($ownerPackage);
    } else {
      $patchOwnerPath = false;
    }

    if (!$this->packagesByName) {
      $this->packagesByName = [];
      $packageRepository = $this->composer->getRepositoryManager()->getLocalRepository();
      foreach ($packageRepository->getPackages() as $package) {
        $this->packagesByName[$package->getName()] = $package;
      }
    }

    foreach ($patches as $patchTarget => $packagePatches) {
      if (!isset($_patches[$patchTarget])) {
        $_patches[$patchTarget] = array();
      }

      foreach ($packagePatches as $label => $data) {
        $isExtendedFormat = is_array($data) && is_numeric($label) && isset($data['label'], $data['url']);

        if ($isExtendedFormat) {
          $label = $data['label'];
          $url = $data['url'];

          if (isset($data['require']) && array_diff_key($this->packagesByName, $data['require'])) {
            continue;
          }
        } else {
          $url = $data;
        }

        if ($patchOwnerPath) {
          $absolutePatchPath = $patchOwnerPath . '/' . $url;

          if (strpos($absolutePatchPath, $vendorDir) === 0) {
            $url = trim(substr($absolutePatchPath, strlen($vendorDir)), '/');
          }
        }

        $_patches[$patchTarget][$url] = $label;
      }
    }

    return $_patches;
  }


  /**
   * Before running composer install,
   * @param Event $event
   */
  public function checkPatches(Event $event) {
    if (!$this->isPatchingEnabled()) {
      return;
    }

    try {
      $repositoryManager = $this->composer->getRepositoryManager();
      $installationManager = $this->composer->getInstallationManager();

      $localRepository = $repositoryManager->getLocalRepository();
      $packages = $localRepository->getPackages();

      $tmp_patches = (array)$this->grabPatches();

      foreach ($packages as $package) {
        $extra = $package->getExtra();

        if (!isset($extra['patches'])) {
          continue;
        }

        $patches = isset($extra['patches']) ? $extra['patches'] : array();
        $patches = $this->preparePatchDefinitions($patches, $package);

        $this->installedPatches[$package->getName()] = $patches;

        foreach ($patches as $targetPackage => $packagePatches) {
          if (!isset($tmp_patches[$targetPackage])) {
            $tmp_patches[$targetPackage] = array();
          }

          $tmp_patches[$targetPackage] = array_merge($packagePatches, $tmp_patches[$targetPackage]);
        }
      }

      // Remove packages for which the patch set has changed.
      foreach ($packages as $package) {
        if (!($package instanceof AliasPackage)) {
          $package_name = $package->getName();

          $extra = $package->getExtra();

          $hasPatches = isset($tmp_patches[$package_name]);
          $hasAppliedPatches = isset($extra['patches_applied']);

          if (!$hasPatches && !$hasAppliedPatches) {
            continue;
          }

          $allPatches = $hasPatches ? $tmp_patches[$package_name] : array();
          $appliedPatches = $hasAppliedPatches? $extra['patches_applied'] : array();

          if (array_diff_key($allPatches, $appliedPatches) || array_diff_key($appliedPatches, $allPatches)) {
            $uninstallOperation = new UninstallOperation($package, 'Removing package so it can be re-installed and re-patched.');
            $this->io->write('<info>Removing package ' . $package_name . ' so that it can be re-installed and re-patched.</info>');
            $installationManager->uninstall($localRepository, $uninstallOperation);
          }
        }
      }
    }
      // If the Locker isn't available, then we don't need to do this.
      // It's the first time packages have been installed.
    catch (\LogicException $e) {
      return;
    }
  }

  /**
   * Gather patches from dependencies and store them for later use.
   *
   * @param PackageEvent $event
   */
  public function gatherPatches(PackageEvent $event) {
    // If patching has been disabled, bail out here.
    if (!$this->isPatchingEnabled()) {
      return;
    }

    if (!isset($this->patches['_patchesGathered'])) {
      $this->patches = (array)$this->grabPatches();
      $this->patches['_patchesGathered'] = true;
    }

    // Now add all the patches from dependencies that will be installed.
    $operations = $event->getOperations();
    $this->io->write('<info>Gathering patches for dependencies. This might take a minute.</info>');
    foreach ($operations as $operation) {
      if ($operation->getJobType() == 'install' || $operation->getJobType() == 'update') {
        $package = $this->getPackageFromOperation($operation);
        $extra = $package->getExtra();
        $packageName = $package->getName();

        if ($operation->getJobType() == 'install') {
          unset($extra['patches_applied']);
          $package->setExtra($extra);
          $this->patches['_patchesGathered'] = true;
        }

        if (isset($extra['patches'])) {
          $patches = $this->preparePatchDefinitions($extra['patches'], $package);

          foreach ($patches as $targetPackage => $packagePatches) {
            if (!isset($this->patches[$targetPackage])) {
              $this->patches[$targetPackage] = array();
            }

            $this->patches[$targetPackage] = array_merge($packagePatches, $this->patches[$targetPackage]);
          }
        }
        // Unset installed patches for this package
        if(isset($this->installedPatches[$packageName])) {
          unset($this->installedPatches[$packageName]);
        }
      }
    }

    // Merge installed patches from dependencies that did not receive an update.
    foreach ($this->installedPatches as $patches) {
      foreach ($patches as $targetPackage => $packagePatches) {
        if (!isset($this->patches[$targetPackage])) {
          $this->patches[$targetPackage] = array();
        }
        
        $this->patches[$targetPackage] = array_merge($packagePatches, $this->patches[$targetPackage]);
      }
    }

    // If we're in verbose mode, list the projects we're going to patch.
    if ($this->io->isVerbose()) {
      foreach ($this->patches as $package => $patches) {
        $number = count($patches);
        $this->io->write('<info>Found ' . $number . ' patches for ' . $package . '.</info>');
      }
    }
  }

  /**
   * Get the patches from root composer or external file
   * @return Patches
   * @throws \Exception
   */
  public function grabPatches() {
    // First, try to get the patches from the root composer.json.
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches'])) {
      $this->io->write('<info>Gathering patches for root package.</info>');
      $patches = $extra['patches'];

      return $this->preparePatchDefinitions($patches);
    }

    // If it's not specified there, look for a patches-file definition.
    elseif (isset($extra['patches-file'])) {
      $this->io->write('<info>Gathering patches from patch file.</info>');
      $patches = file_get_contents($extra['patches-file']);
      $patches = json_decode($patches, TRUE);
      $error = json_last_error();
      if ($error != 0) {
        switch ($error) {
          case JSON_ERROR_DEPTH:
            $msg = ' - Maximum stack depth exceeded';
            break;
          case JSON_ERROR_STATE_MISMATCH:
            $msg =  ' - Underflow or the modes mismatch';
            break;
          case JSON_ERROR_CTRL_CHAR:
            $msg = ' - Unexpected control character found';
            break;
          case JSON_ERROR_SYNTAX:
            $msg =  ' - Syntax error, malformed JSON';
            break;
          case JSON_ERROR_UTF8:
            $msg =  ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
          default:
            $msg =  ' - Unknown error';
            break;
          }
          throw new \Exception('There was an error in the supplied patches file:' . $msg);
        }
      if (isset($patches['patches'])) {
        $patches = $patches['patches'];
        return $this->preparePatchDefinitions($patches);
      }
      elseif(!$patches) {
        throw new \Exception('There was an error in the supplied patch file');
      }
    }

    return array();
  }

  /**
   * @param Event $event
   * @throws \Exception
   */
  public function postInstall(Event $event) {
    $packageRepository = $this->composer->getRepositoryManager()->getLocalRepository();

    $vendorDir = $this->composer->getConfig()->get('vendor-dir');
    $manager = $event->getComposer()->getInstallationManager();

    $packagesUpdated = false;
    foreach ($packageRepository->getPackages() as $package) {
      $packageName = $package->getName();

      if (!isset($this->patches[$packageName])) {
        if ($this->io->isVerbose()) {
          $this->io->write('<info>No patches found for ' . $packageName . '.</info>');
        }
        continue;
      }

      $extra = $package->getExtra();

      if (isset($extra['patches_applied']) && $extra['patches_applied']) {
        continue;
      }

      $this->io->write('  - Applying patches for <info>' . $packageName . '</info>');

      $installPath = $manager->getInstaller($package->getType())->getInstallPath($package);

      $downloader = new RemoteFilesystem($this->io, $this->composer->getConfig());

      // Track applied patches in the package info in installed.json
      $extra['patches_applied'] = array();

      $allPackagePatchesApplied = true;
      foreach ($this->patches[$packageName] as $url => $description) {
        $urlLabel = '<info>' . $url . '</info>';
        $absolutePatchPath = $vendorDir . '/' . $url;

        if (file_exists($absolutePatchPath)) {
          $ownerName  = implode('/', array_slice(explode('/', $url), 0, 2));

          $urlLabel = '<comment>' . $ownerName . '</comment>: ' . '<info>' .
            trim(substr($url, strlen($ownerName)), '/') . '</info>';

          $url = $absolutePatchPath;
        }

        $this->io->write('    ~ ' . $urlLabel . ' (<comment>' . $description. '</comment>)');
        
        try {
          $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::PRE_PATCH_APPLY, $package, $url, $description));

          $this->getAndApplyPatch($downloader, $installPath, $url);

          $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::POST_PATCH_APPLY, $package, $url, $description));

          $appliedPatchPath = $url;
          if (strpos($appliedPatchPath, $vendorDir) === 0) {
            $appliedPatchPath = trim(substr($appliedPatchPath, strlen($vendorDir)), '/');
          }

          $extra['patches_applied'][$appliedPatchPath] = $description;
        } catch (\Exception $e) {
          $this->io->write('   <error>Could not apply patch! Skipping.</error>');

          $allPackagePatchesApplied = false;

          if ($this->io->isVerbose()) {
            $this->io->write('<warning>' . trim($e->getMessage(), "\n ") . '</warning>');
          }

          if (getenv('COMPOSER_EXIT_ON_PATCH_FAILURE')) {
            throw new \Exception("Cannot apply patch $description ($url)!");
          }
        }
      }

      if ($allPackagePatchesApplied) {
        $packagesUpdated = true;
        $package->setExtra($extra);
      }

      $this->io->write('');
      $this->writePatchReport($this->patches[$packageName], $installPath);
    }

    if ($packagesUpdated) {
      $packageRepository->write();
    }
  }

  /**
   * Get a Package object from an OperationInterface object.
   *
   * @param OperationInterface $operation
   * @return PackageInterface
   * @throws \Exception
   */
  protected function getPackageFromOperation(OperationInterface $operation) {
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }
    else {
      throw new \Exception('Unknown operation: ' . get_class($operation));
    }

    return $package;
  }

  /**
   * Apply a patch on code in the specified directory.
   *
   * @param RemoteFilesystem $downloader
   * @param $install_path
   * @param $patch_url
   * @throws \Exception
   */
  protected function getAndApplyPatch(RemoteFilesystem $downloader, $install_path, $patch_url) {

    // Local patch file.
    if (file_exists($patch_url)) {
      $filename = realpath($patch_url);
    }
    else {
      // Generate random (but not cryptographically so) filename.
      $filename = uniqid("/tmp/") . ".patch";

      // Download file from remote filesystem to this location.
      $hostname = parse_url($patch_url, PHP_URL_HOST);
      $downloader->copy($hostname, $patch_url, $filename, FALSE);
    }

    // Modified from drush6:make.project.inc
    $patched = FALSE;
    // The order here is intentional. p1 is most likely to apply with git apply.
    // p0 is next likely. p2 is extremely unlikely, but for some special cases,
    // it might be useful.
    $patch_levels = array('-p1', '-p0', '-p2');
    foreach ($patch_levels as $patch_level) {
      $checked = $this->executeCommand('cd %s && GIT_DIR=. git apply --check %s %s', $install_path, $patch_level, $filename);
      if ($checked) {
        // Apply the first successful style.
        $patched = $this->executeCommand('cd %s && GIT_DIR=. git apply %s %s', $install_path, $patch_level, $filename);
        break;
      }
    }

    // In some rare cases, git will fail to apply a patch, fallback to using
    // the 'patch' command.
    if (!$patched) {
      foreach ($patch_levels as $patch_level) {
        // --no-backup-if-mismatch here is a hack that fixes some
        // differences between how patch works on windows and unix.
        if ($patched = $this->executeCommand("patch %s --no-backup-if-mismatch -d %s < %s", $patch_level, $install_path, $filename)) {
          break;
        }
      }
    }

    // Clean up the temporary patch file.
    if (isset($hostname)) {
      unlink($filename);
    }
    // If the patch *still* isn't applied, then give up and throw an Exception.
    // Otherwise, let the user know it worked.
    if (!$patched) {
      throw new \Exception("Cannot apply patch $patch_url");
    }
  }

  /**
   * Checks if the root package enables patching.
   *
   * @return bool
   *   Whether patching is enabled. Defaults to TRUE.
   */
  protected function isPatchingEnabled() {
    $extra = $this->composer->getPackage()->getExtra();

    if (empty($extra['patches'])) {
      // The root package has no patches of its own, so only allow patching if
      // it has specifically opted in.
      return isset($extra['enable-patching']) ? $extra['enable-patching'] : FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Writes a patch report to the target directory.
   *
   * @param array $patches
   * @param string $directory
   */
  protected function writePatchReport($patches, $directory) {
    $output = "This file was automatically generated by Composer Patches (https://github.com/cweagans/composer-patches)\n";
    $output .= "Patches applied to this directory:\n\n";
    foreach ($patches as $url => $description) {
      $output .= $description . "\n";
      $output .= 'Source: ' . $url . "\n\n\n";
    }
    file_put_contents($directory . "/PATCHES.txt", $output);
  }

  /**
   * Executes a shell command with escaping.
   *
   * @param string $cmd
   * @return bool
   */
  protected function executeCommand($cmd) {
    // Shell-escape all arguments except the command.
    $args = func_get_args();
    foreach ($args as $index => $arg) {
      if ($index !== 0) {
        $args[$index] = escapeshellarg($arg);
      }
    }

    // And replace the arguments.
    $command = call_user_func_array('sprintf', $args);
    $output = '';
    if ($this->io->isVerbose()) {
      $this->io->write('<comment>' . $command . '</comment>');
      $io = $this->io;
      $output = function ($type, $data) use ($io) {
        if ($type == Process::ERR) {
          $io->write('<error>' . $data . '</error>');
        }
        else {
          $io->write('<comment>' . $data . '</comment>');
        }
      };
    }
    return ($this->executor->execute($command, $output) == 0);
  }
}
