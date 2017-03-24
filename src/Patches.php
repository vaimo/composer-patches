<?php
namespace Vaimo\ComposerPatches;

class Patches
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
     * @var \Vaimo\ComposerPatches\Patch\Applier
     */
    protected $patchApplier;

    /**
     * @var \Vaimo\ComposerPatches\Json\Decoder
     */
    protected $jsonDecoder;

    /**
     * @var \Composer\Package\Version\VersionParser
     */
    protected $versionParser;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Report
     */
    protected $patchReport;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Config
     */
    protected $config;

    /**
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composer,
        \Composer\IO\IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;

        $this->eventDispatcher = $composer->getEventDispatcher();

        $executor = new \Composer\Util\ProcessExecutor($this->io);
        $this->patchApplier = new \Vaimo\ComposerPatches\Patch\Applier($executor, $this->io);

        $this->config = new \Vaimo\ComposerPatches\Patch\Config($composer);

        $this->jsonDecoder = new \Vaimo\ComposerPatches\Json\Decoder();
        $this->versionParser = new \Composer\Package\Version\VersionParser();
        $this->patchReport = new \Vaimo\ComposerPatches\Patch\Report();
    }

    public function resetAppliedPatchesInfoForPackage(\Composer\Package\PackageInterface $package)
    {
        $extra = $package->getExtra();

        unset($extra['patches_applied']);

        $package->setExtra($extra);
    }

    protected function preparePatchDefinitions($patches, $ownerPackage = null)
    {
        $patchesPerPackage = array();

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');

        if ($ownerPackage) {
            $manager = $this->composer->getInstallationManager();

            $packageInstaller = $manager->getInstaller($ownerPackage->getType());
            $patchOwnerPath = $packageInstaller->getInstallPath($ownerPackage);
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
                $isExtendedFormat = is_array($data) && (isset($data['url']) || isset($data['source']));

                $versionLimitation = false;

                if ($isExtendedFormat) {
                    $source = isset($data['url']) ? $data['url'] : $data['source'];
                    $label = isset($data['label']) ? $data['label'] : $label;
                    $versionLimitation = isset($data['version']) ? $data['version'] : false;
                } else {
                    $source = (string)$data;
                }

                if ($versionLimitation && isset($this->packagesByName[$patchTarget])) {
                    $targetPackage = $this->packagesByName[$patchTarget];

                    $packageConstraint = $this->versionParser->parseConstraints($targetPackage->getVersion());
                    $patchConstraint = $this->versionParser->parseConstraints($versionLimitation);

                    if (!$patchConstraint->matches($packageConstraint)) {
                        continue;
                    }
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

    public function resolvePackagesToReinstall($packages, $patches)
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $reinstallQueue = [];

        foreach ($packages as $package) {
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

            if (!$appliedPatches = $extra['patches_applied']) {
                continue;
            }

            foreach ($packagePatches as $url => &$description) {
                $absolutePatchPath = $vendorDir . '/' . $url;

                if (file_exists($absolutePatchPath)) {
                    $url = $absolutePatchPath;
                }

                $description = $description . ', md5:' . md5_file($url);
            }

            if (!array_diff_assoc($appliedPatches, $packagePatches) && !array_diff_assoc($packagePatches, $appliedPatches)) {
                continue;
            }

            $reinstallQueue[] = $package->getName();
        }

        return $reinstallQueue;
    }

    public function applyPatches()
    {
        $forceReinstall = getenv(\Vaimo\ComposerPatches\Environment::FORCE_REAPPLY);

        $installationManager = $this->composer->getInstallationManager();
        $packageRepository = $this->composer->getRepositoryManager()->getLocalRepository();

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');

        $packagesUpdated = false;

        if ($this->config->isPatchingEnabled()) {
            $patches = $this->collectPatches();
        } else {
            $patches = array();
        }

        $installedPackages = $packageRepository->getPackages();

        if ($forceReinstall) {
            $reInstallationQueue = array_keys($patches);
        } else {
            $reInstallationQueue = $this->resolvePackagesToReinstall($installedPackages, $patches);
        }

        /**
         * Detect targeted packages that have patches removed or changed
         */
        foreach ($packageRepository->getPackages() as $package) {
            $packageName = $package->getName();
            $extra = $package->getExtra();

            if (!isset($extra['patches_applied'])) {
                continue;
            }

            if (!isset($patches[$packageName]) || array_diff_key($extra['patches_applied'], $patches[$packageName])) {
                $reInstallationQueue[] = $packageName;
            }
        }

        /**
         * Re-install packages (reset patches)
         */
        if ($reInstallationQueue) {
            $this->io->write('<info>Re-installing packages that were targeted by patches.</info>');

            foreach (array_unique($reInstallationQueue) as $packageName) {
                $package = $packageRepository->findPackage($packageName, '*');

                if (!$package) {
                    continue;
                }

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

            $this->io->write(sprintf('  - Applying patches for <info>%s</info>', $packageName));

            $packageInstaller = $installationManager->getInstaller($package->getType());
            $packageInstallPath = $packageInstaller->getInstallPath($package);

            $downloader = new \Composer\Util\RemoteFilesystem($this->io, $this->composer->getConfig());

            $extra['patches_applied'] = array();

            $allPackagePatchesApplied = true;
            foreach ($packagePatches as $source => $description) {
                $patchLabel = sprintf('<info>%s</info>', $source);
                $absolutePatchPath = $vendorDir . '/' . $source;

                if (file_exists($absolutePatchPath)) {
                    $ownerName  = implode('/', array_slice(explode('/', $source), 0, 2));

                    $patchLabel = sprintf('<info>%s: %s</info>', $ownerName, trim(substr($source, strlen($ownerName)), '/'));;

                    $source = $absolutePatchPath;
                }

                $this->io->write(sprintf('    ~ %s', $patchLabel));
                $this->io->write(sprintf('      <comment>%s</comment>', $description));

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

            $this->patchReport->generate($packagePatches, $packageInstallPath);
        }

        if ($packagesUpdated) {
            $packageRepository->write();
        }
    }
}
