<?php
namespace Vaimo\ComposerPatches;

class Patches implements \Composer\Plugin\PluginInterface, \Composer\EventDispatcher\EventSubscriberInterface
{
    /**
     * @var \Composer\Composer $composer
     */
    protected $composer;

    /**
     * @var \Composer\IO\IOInterface $io
     */
    protected $io;

    /**
     * @var \Composer\EventDispatcher\EventDispatcher $eventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var \Composer\Util\ProcessExecutor $executor
     */
    protected $executor;

    /**
     * @var array $installedPatches
     */
    protected $installedPatches;

    /**
     * @var array $packagesByName
     */
    protected $packagesByName;

    /**
     * @var array $excludedPatches
     */
    protected $excludedPatches;

    /**
     * @var array
     */
    protected $packagesToReinstall = array();

    /**
     * Note that postInstall is locked to autoload dump instead of post-install. Reason for this is that
     * post-install comes after auto-loader generation which means that in case patches target class
     * namespaces or class names, the auto-loader will not get those changes applied to it correctly.
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Composer\Installer\PackageEvents::POST_PACKAGE_UNINSTALL => 'removePatches',
            \Composer\Installer\PackageEvents::PRE_PACKAGE_INSTALL => 'resetAppliedPatches',
            \Composer\Installer\PackageEvents::PRE_PACKAGE_UPDATE => 'resetAppliedPatches',
            \Composer\Script\ScriptEvents::PRE_AUTOLOAD_DUMP => 'postInstall'
        );
    }

    public function activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->eventDispatcher = $composer->getEventDispatcher();
        $this->executor = new \Composer\Util\ProcessExecutor($this->io);
        $this->installedPatches = array();
    }

    public function resetAppliedPatches(\Composer\Installer\PackageEvent $event)
    {
        foreach ($event->getOperations() as $operation) {
            if ($operation->getJobType() != 'install') {
                continue;
            }

            $package = $this->getPackageFromOperation($operation);
            $extra = $package->getExtra();

            unset($extra['patches_applied']);

            $package->setExtra($extra);
        }
    }

    protected function preparePatchDefinitions($patches, $ownerPackage = null)
    {
        $patchesPerPackage = array();

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

        $excludedPatches = $this->getExcludedPatches();

        foreach ($patches as $patchTarget => $packagePatches) {
            if (!isset($patchesPerPackage[$patchTarget])) {
                $patchesPerPackage[$patchTarget] = array();
            }

            foreach ($packagePatches as $label => $data) {
                $isExtendedFormat = is_array($data) && is_numeric($label) && isset($data['label'], $data['url']);

                if ($isExtendedFormat) {
                    $label = $data['label'];
                    $url = (string)$data['url'];

                    if (isset($data['require']) && array_diff_key($data['require'], $this->packagesByName)) {
                        continue;
                    }
                } else {
                    $url = (string)$data;
                }

                if ($ownerPackage) {
                    $ownerPackageName = $ownerPackage->getName();

                    if (isset($excludedPatches[$ownerPackageName][$url])) {
                        continue;
                    }
                }

                if ($patchOwnerPath) {
                    $absolutePatchPath = $patchOwnerPath . '/' . $url;

                    if (strpos($absolutePatchPath, $vendorDir) === 0) {
                        $url = trim(substr($absolutePatchPath, strlen($vendorDir)), '/');
                    }
                }

                $patchesPerPackage[$patchTarget][$url] = $label;
            }
        }

        return array_filter($patchesPerPackage);
    }

    protected function collectPatches()
    {
        $repositoryManager = $this->composer->getRepositoryManager();

        $localRepository = $repositoryManager->getLocalRepository();
        $projectPatches = $this->getProjectPatches();

        $packages = $localRepository->getPackages();

        foreach ($packages as $package) {
            $extra = $package->getExtra();

            if (!isset($extra['patches'])) {
                continue;
            }

            $patches = isset($extra['patches']) ? $extra['patches'] : array();
            $patches = $this->preparePatchDefinitions($patches, $package);

            $this->installedPatches[$package->getName()] = $patches;

            foreach ($patches as $targetPackage => $packagePatches) {
                if (!isset($projectPatches[$targetPackage])) {
                    $projectPatches[$targetPackage] = array();
                }

                $projectPatches[$targetPackage] = array_merge(
                    $packagePatches,
                    $projectPatches[$targetPackage]
                );
            }
        }

        return $projectPatches;
    }

    public function getExcludedPatches()
    {
        $extra = $this->composer->getPackage()->getExtra();

        if (!$this->excludedPatches) {
            $this->excludedPatches = array();

            if (isset($extra['excluded-patches'])) {
                foreach ($extra['excluded-patches'] as $patchOwner => $patches) {
                    if (!isset($this->excludedPatches[$patchOwner])) {
                        $this->excludedPatches[$patchOwner] = array();
                    }

                    $this->excludedPatches[$patchOwner] = array_flip($patches);
                }
            }
        }

        return $this->excludedPatches;
    }

    public function getProjectPatches()
    {
        $extra = $this->composer->getPackage()->getExtra();

        if (isset($extra['patches'])) {
            $this->io->write('<info>Gathering patches for root package.</info>');
            $patches = $extra['patches'];

            return $this->preparePatchDefinitions($patches);
        } elseif (isset($extra['patches-file'])) {
            $this->io->write('<info>Gathering patches from patch file.</info>');

            $patches = file_get_contents($extra['patches-file']);
            $patches = json_decode($patches, true);
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
            } elseif (!$patches) {
                throw new \Exception('There was an error in the supplied patch file');
            }
        }

        return array();
    }

    public function removePatches(\Composer\Installer\PackageEvent $event)
    {
        $operations = $event->getOperations();

        foreach ($operations as $operation) {
            if (!$operation instanceof \Composer\DependencyResolver\Operation\UninstallOperation) {
                continue;
            }

            $package = $operation->getPackage();
            $extra = $package->getExtra();

            if (!isset($extra['patches'])) {
                continue;
            }

            $patches = $this->preparePatchDefinitions($extra['patches'], $package);

            foreach ($patches as $targetPackageName => $packagePatches) {
                $this->packagesToReinstall[] = $targetPackageName;
            }
        }
    }

    public function resolvePackagesToReinstall($installedPackages)
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $reinstallQueue = [];

        foreach ($installedPackages as $package) {
            $packageName = $package->getName();

            if (isset($patches[$packageName])) {
                $packagePatches = $patches[$packageName];
            } else {
                $packagePatches = array();
            }

            $extra = $package->getExtra();

            if (!isset($extra['patches_applied'])) {
                continue;
            }

            if (isset($extra['patches_applied'])) {
                $applied = $extra['patches_applied'];

                if (!$applied) {
                    continue;
                }

                foreach ($packagePatches as $url => &$description) {
                    $absolutePatchPath = $vendorDir . '/' . $url;

                    if (file_exists($absolutePatchPath)) {
                        $url = $absolutePatchPath;
                    }

                    $description = $description . ', md5:' . md5_file($url);
                }

                if (!array_diff_assoc($applied, $packagePatches) && !array_diff_assoc($packagePatches, $applied)) {
                    continue;
                }
            }

            $reinstallQueue[] = $package->getName();
        }

        return $reinstallQueue;
    }

    public function postInstall(\Composer\Script\Event $event)
    {
        $forceReinstall = getenv('COMPOSER_FORCE_REAPPLY');

        $installationManager = $this->composer->getInstallationManager();
        $packageRepository = $this->composer->getRepositoryManager()->getLocalRepository();

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $manager = $event->getComposer()->getInstallationManager();

        $packagesUpdated = false;

        if ($this->isPatchingEnabled()) {
            $patches = $this->collectPatches();
        } else {
            $patches = array();
        }

        $installedPackages = $packageRepository->getPackages();

        if ($forceReinstall) {
            $this->packagesToReinstall = array_keys($patches);
        } else {
            $this->packagesToReinstall = array_merge(
                $this->packagesToReinstall,
                $this->resolvePackagesToReinstall($installedPackages)
            );
        }

        if ($this->packagesToReinstall) {
            $this->io->write('<info>Re-installing packages that were targeted by patches.</info>');

            foreach (array_unique($this->packagesToReinstall) as $packageName) {
                $package = $packageRepository->findPackage($packageName, '*');

                $uninstallOperation = new \Composer\DependencyResolver\Operation\InstallOperation(
                    $package,
                    'Re-installing package.'
                );

                $installationManager->install($packageRepository, $uninstallOperation);

                $extra = $package->getExtra();

                unset($extra['patches_applied']);

                $packagesUpdated = true;
                $package->setExtra($extra);
            }
        }

        /**
         * Apply patches
         */
        foreach ($packageRepository->getPackages() as $package) {
            $packageName = $package->getName();

            if (!isset($patches[$packageName])) {
                continue;
            }

            $packagePatches = $patches[$packageName];
            $extra = $package->getExtra();

            foreach ($packagePatches as $source => &$description) {
                $absolutePatchPath = $vendorDir . '/' . $source;

                if (file_exists($absolutePatchPath)) {
                    $source = $absolutePatchPath;
                }

                $description = $description . ', md5:' . md5_file($source);
            }

            if (isset($extra['patches_applied'])) {
                $applied = $extra['patches_applied'];

                if (!array_diff_assoc($applied, $packagePatches) && !array_diff_assoc($packagePatches, $applied)) {
                    continue;
                }
            }

            $packagePatches = $patches[$packageName];

            $this->io->write('  - Applying patches for <info>' . $packageName . '</info>');

            $packageInstaller = $manager->getInstaller($package->getType());
            $packageInstallPath = $packageInstaller->getInstallPath($package);

            $downloader = new \Composer\Util\RemoteFilesystem($this->io, $this->composer->getConfig());

            // Track applied patches in the package info in installed.json
            $extra['patches_applied'] = array();

            $allPackagePatchesApplied = true;
            foreach ($packagePatches as $source => $description) {
                $patchLabel = '<info>' . $source . '</info>';
                $absolutePatchPath = $vendorDir . '/' . $source;

                if (file_exists($absolutePatchPath)) {
                    $ownerName  = implode('/', array_slice(explode('/', $source), 0, 2));

                    $patchLabel = '<info>' . $ownerName . ': ' . trim(substr($source, strlen($ownerName)), '/') . '</info>';

                    $source = $absolutePatchPath;
                }

                $this->io->write('    ~ ' . $patchLabel);
                $this->io->write('      ' . '<comment>' . $description. '</comment>');

                try {
                    $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::PRE_PATCH_APPLY, $package, $source, $description));

                    if (file_exists($source)) {
                        $filename = realpath($source);
                    } else {
                        $filename = uniqid('/tmp/') . '.patch';
                        $hostname = parse_url($source, PHP_URL_HOST);

                        $downloader->copy($hostname, $source, $filename, false);
                    }

                    $this->applyPatch($filename, $packageInstallPath);

                    if (isset($hostname)) {
                        unlink($filename);
                    }

                    $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::POST_PATCH_APPLY, $package, $source, $description));

                    $appliedPatchPath = $source;

                    if (strpos($appliedPatchPath, $vendorDir) === 0) {
                        $appliedPatchPath = trim(substr($appliedPatchPath, strlen($vendorDir)), '/');
                    }

                    $extra['patches_applied'][$appliedPatchPath] = $description . ', md5:' . md5_file($source);
                } catch (\Exception $e) {
                    $this->io->write('   <error>Could not apply patch! Skipping.</error>');

                    $allPackagePatchesApplied = false;

                    if ($this->io->isVerbose()) {
                        $this->io->write('<warning>' . trim($e->getMessage(), "\n ") . '</warning>');
                    }

                    if (getenv('COMPOSER_EXIT_ON_PATCH_FAILURE')) {
                        throw new \Exception(sprintf('Cannot apply patch %s (%s)!', $description, $source));
                    }
                }
            }

            if ($allPackagePatchesApplied) {
                $packagesUpdated = true;
                ksort($extra);
                $package->setExtra($extra);
            }

            $this->io->write('');
            $this->writePatchReport($packagePatches, $packageInstallPath);
        }

        if ($packagesUpdated) {
            $packageRepository->write();
        }
    }

    protected function getPackageFromOperation(\Composer\DependencyResolver\Operation\OperationInterface $operation)
    {
        if ($operation instanceof \Composer\DependencyResolver\Operation\InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof \Composer\DependencyResolver\Operation\UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            throw new \Exception(sprintf('Unknown operation: %s', get_class($operation)));
        }

        return $package;
    }

    /**
     * Enabled by default if there are project packages that include patches, but root package can still
     * explicitly disable them.
     *
     * @return bool
     */
    protected function isPatchingEnabled()
    {
        $extra = $this->composer->getPackage()->getExtra();

        if (empty($extra['patches'])) {
            return isset($extra['enable-patching']) ? $extra['enable-patching'] : false;
        } else {
            return isset($extra['enable-patching']) && !$extra['enable-patching'] ? false : true;
        }
    }

    protected function writePatchReport($patches, $directory) {
        $outputLines = array();
        $outputLines[] = 'This file was automatically generated by Composer Patches';
        $outputLines[] = 'Patches applied to this directory:';
        $outputLines[] = '';

        foreach ($patches as $source => $description) {
            $outputLines[] = $description;
            $outputLines[] = 'Source: ' . $source;
            $outputLines[] = '';
            $outputLines[] = '';
        }

        file_put_contents($directory . '/PATCHES.txt', implode("\n", $outputLines));
    }

    protected function applyPatch($filename, $cwd)
    {
        $patchApplied = false;
        $patchLevelSequence = array('-p1', '-p0', '-p2');

        foreach ($patchLevelSequence as $patchLevel) {
            $patchValidated = $this->executeCommand('git apply --check %s %s', [$patchLevel, $filename], $cwd);

            if (!$patchValidated) {
                continue;
            }

            $patchApplied = $this->executeCommand('git apply %s %s', [$patchLevel, $filename], $cwd);

            if ($patchApplied) {
                break;
            }
        }

        if (!$patchApplied) {
            foreach ($patchLevelSequence as $patchLevel) {
                $patchApplied = $this->executeCommand('patch %s --no-backup-if-mismatch < %s', [$patchLevel, $filename], $cwd);

                if ($patchApplied) {
                    break;
                }
            }
        }

        if (isset($hostname)) {
            unlink($filename);
        }

        if (!$patchApplied) {
            throw new \Exception(sprintf('Cannot apply patch %s', $filename));
        }
    }

    protected function executeCommand($commandTemplate, array $arguments, $cwd = null)
    {
        foreach ($arguments as $index => $argument) {
            $arguments[$index] = escapeshellarg($argument);
        }

        $command = vsprintf($commandTemplate, $arguments);

        $outputHandler = '';

        if ($this->io->isVerbose()) {
            $io = $this->io;

            $outputHandler = function ($type, $data) use ($io) {
                if ($type == \Symfony\Component\Process\Process::ERR) {
                    $io->write('<error>' . $data . '</error>');
                } else {
                    $io->write('<comment>' . $data . '</comment>');
                }
            };
        }

        return $this->executor->execute($command, $outputHandler, $cwd) == 0;
    }
}
