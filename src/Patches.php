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
     * @var \Vaimo\ComposerPatches\Patch\Applier
     */
    protected $patchApplier;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Report
     */
    protected $patchReport;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Config
     */
    protected $config;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Collector
     */
    protected $patchesCollector;

    /**
     * @var \Vaimo\ComposerPatches\Patch\PathNormalizer
     */
    protected $patchPathNormalizer;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionParser
     */
    protected $patchDefinitionParser;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Constraints
     */
    protected $patchConstraints;

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

        $this->config = new \Vaimo\ComposerPatches\Patch\Config($composer);
        $this->patchReport = new \Vaimo\ComposerPatches\Patch\Report();
        $this->patchApplier = new \Vaimo\ComposerPatches\Patch\Applier($io);

        $this->patchesCollector = new \Vaimo\ComposerPatches\Patch\Collector();
        $this->patchPathNormalizer = new \Vaimo\ComposerPatches\Patch\PathNormalizer($composer);
        $this->patchDefinitionParser = new \Vaimo\ComposerPatches\Patch\DefinitionParser();
        $this->patchConstraints = new \Vaimo\ComposerPatches\Patch\Constraints($composer);
    }

    public function resetAppliedPatchesInfoForPackage(\Composer\Package\PackageInterface $package)
    {
        $extra = $package->getExtra();

        unset($extra['patches_applied']);

        $package->setExtra($extra);
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
        $packages = $packageRepository->getPackages();

        $vendorDir = $this->composer->getConfig()->get('vendor-dir');

        $packagesUpdated = false;

        if ($this->config->isPatchingEnabled()) {
            $patches = $this->patchesCollector->gatherAllPatches(array_merge(
                $packages,
                [$this->composer->getPackage()]
            ));

            $patches = $this->patchConstraints->apply($patches);
            $patches = $this->patchPathNormalizer->process($patches);
            $patches = $this->patchDefinitionParser->simplify($patches);
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
        foreach ($packages as $package) {
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
