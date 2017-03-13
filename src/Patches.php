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
     * @var \Vaimo\ComposerPatches\Patch\Applier
     */
    protected $patchApplier;

    /**
     * @var \Vaimo\ComposerPatches\Json\Decoder
     */
    protected $jsonDecoder;

    /**
     * @var \Vaimo\ComposerPatches\Json\Utils
     */
    protected $composerUtils;

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

        $executor = new \Composer\Util\ProcessExecutor($this->io);

        $this->patchApplier = new \Vaimo\ComposerPatches\Patch\Applier($executor, $this->io);
        $this->jsonDecoder = new \Vaimo\ComposerPatches\Json\Decoder();
        $this->composerUtils = new \Vaimo\ComposerPatches\Json\Utils();
    }

    public function resetAppliedPatches(\Composer\Installer\PackageEvent $event)
    {
        foreach ($event->getOperations() as $operation) {
            if ($operation->getJobType() != 'install') {
                continue;
            }

            $package = $this->composerUtils->getPackageFromOperation($operation);
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
            $this->packagesByName = array();
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
                $isExtendedFormat = (
                    is_array($data) &&
                    is_numeric($label) &&
                    (
                        isset($data['label'], $data['url']) ||
                        isset($data['label'], $data['source'])
                    )
                );

                if ($isExtendedFormat) {
                    $label = $data['label'];
                    $source = isset($data['url']) ?: $data['source'];
                } else {
                    $source = (string)$data;
                }

                if ($ownerPackage) {
                    $ownerPackageName = $ownerPackage->getName();

                    if (isset($excludedPatches[$ownerPackageName][$source])) {
                        continue;
                    }
                }

                if ($patchOwnerPath) {
                    $absolutePatchPath = $patchOwnerPath . '/' . $source;

                    if (strpos($absolutePatchPath, $vendorDir) === 0) {
                        $source = trim(substr($absolutePatchPath, strlen($vendorDir)), '/');
                    }
                }

                $patchesPerPackage[$patchTarget][$source] = $label;
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

            $patches = $this->jsonDecoder->decode(
                file_get_contents($extra['patches-file'])
            );

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
        $forceReinstall = getenv(\Vaimo\ComposerPatches\Environment::FORCE_REAPPLY);

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

                    $this->patchApplier->execute($filename, $packageInstallPath);

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

                    if (getenv(\Vaimo\ComposerPatches\Environment::EXIT_ON_FAIL)) {
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
}
