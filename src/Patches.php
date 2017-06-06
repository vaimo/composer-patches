<?php
namespace Vaimo\ComposerPatches;

use Vaimo\ComposerPatches\Composer\ResetOperation;

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
     * @var \Vaimo\ComposerPatches\Patch\DefinitionsProcessor
     */
    protected $patchDefinitionsProcessor;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Constraints
     */
    protected $patchConstraints;

    /**
     * @var \Vaimo\ComposerPatches\Patch\PackageUtils
     */
    protected $packageUtils;

    /**
     * @var \Composer\Util\RemoteFilesystem
     */
    protected $patchDownloader;

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

        $this->patchDownloader = new \Composer\Util\RemoteFilesystem(
            $this->io,
            $this->composer->getConfig()
        );

        $this->patchesCollector = new \Vaimo\ComposerPatches\Patch\Collector();
        $this->patchPathNormalizer = new \Vaimo\ComposerPatches\Patch\PathNormalizer($composer);
        $this->patchDefinitionsProcessor = new \Vaimo\ComposerPatches\Patch\DefinitionsProcessor();
        $this->patchConstraints = new \Vaimo\ComposerPatches\Patch\Constraints($composer);
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
    }

    public function resetAppliedPatchesInfoForPackage(\Composer\Package\PackageInterface $package)
    {
        $extra = $package->getExtra();

        unset($extra['patches_applied']);

        $package->setExtra($extra);
    }

    public function resolvePackagesToReinstall($packages, $patches)
    {
        $reinstallQueue = array();

        foreach ($packages as $package) {
            $packageName = $package->getName();
            $packagePatches = isset($patches[$packageName]) ? $patches[$packageName] : array();

            if (!$this->packageUtils->shouldReinstall($package, $packagePatches)) {
                continue;
            }

            $reinstallQueue[] = $packageName;
        }

        return $reinstallQueue;
    }

    public function applyPatches()
    {
        $installationManager = $this->composer->getInstallationManager();
        $packageRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $packages = $packageRepository->getPackages();
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');

        if ($patchingEnabled = $this->config->isPatchingEnabled()) {
            $patches = $this->patchesCollector->gatherAllPatches(
                array_merge($packages, [$this->composer->getPackage()])
            );

            $patches = $this->patchConstraints->apply($patches);
            $patches = $this->patchPathNormalizer->process($patches);

            $patches = $this->patchDefinitionsProcessor->validate($patches, $vendorDir);
            $patches = $this->patchDefinitionsProcessor->flatten($patches);
        } else {
            $patches = array();
        }

        $packageResetFlags = array_fill_keys(
            !getenv(Environment::FORCE_REAPPLY) || !$patchingEnabled
                ? $this->resolvePackagesToReinstall($packageRepository->getPackages(), $patches)
                : array_keys($patches),
            true
        );

        $packagesUpdated = false;
        foreach ($packageRepository->getPackages() as $package) {
            $packageName = $package->getName();

            if (isset($packageResetFlags[$packageName])) {
                $installationManager->install($packageRepository, new ResetOperation(
                    $package,
                    'Re-installing package due to patch configuration change'
                ));

                $packagesUpdated = $this->packageUtils->resetAppliedPatches($package);
            }

            if (!isset($patches[$packageName])) {
                continue;
            }

            $patchesForPackage = $patches[$packageName];

            if (!$this->packageUtils->hasPatchChanges($package, $patchesForPackage)) {
                continue;
            }

            $extra = $package->getExtra();
            $extra['patches_applied'] = array();

            $this->io->write(sprintf('  - Applying patches for <info>%s</info>', $packageName));

            $packageInstaller = $installationManager->getInstaller($package->getType());
            $packageInstallPath = $packageInstaller->getInstallPath($package);

            $allPackagePatchesApplied = true;

            foreach ($patchesForPackage as $source => $description) {
                $relativePath = $source;

                $patchSourceLabel = sprintf('<info>%s</info>', $source);
                $absolutePatchPath = $vendorDir . '/' . $source;
                $patchComment = substr($description, 0, strrpos($description, ','));

                if (file_exists($absolutePatchPath)) {
                    $ownerName  = implode('/', array_slice(explode('/', $source), 0, 2));

                    $patchSourceLabel = sprintf(
                        '<info>%s: %s</info>',
                        $ownerName,
                        trim(substr($source, strlen($ownerName)), '/')
                    );

                    $source = $absolutePatchPath;
                }

                $this->io->write(sprintf('    ~ %s', $patchSourceLabel));
                $this->io->write(sprintf('      <comment>%s</comment>', $patchComment));

                try {
                    $this->eventDispatcher->dispatch(
                        null,
                        new PatchEvent(PatchEvents::PRE_PATCH_APPLY, $package, $source, $description)
                    );

                    if (file_exists($source)) {
                        $filename = realpath($source);
                    } else {
                        $filename = uniqid('/tmp/') . '.patch';

                        $hostname = parse_url($source, PHP_URL_HOST);
                        $this->patchDownloader->copy($hostname, $source, $filename, false);
                    }

                    $this->patchApplier->execute($filename, $packageInstallPath);

                    if (isset($hostname)) {
                        unset($hostname);
                        unlink($filename);
                    }

                    $this->eventDispatcher->dispatch(
                        null,
                        new PatchEvent(PatchEvents::POST_PATCH_APPLY, $package, $source, $description)
                    );

                    $extra['patches_applied'][$relativePath] = $description;
                } catch (\Exception $e) {
                    $allPackagePatchesApplied = false;

                    if ($this->io->isVerbose()) {
                        $this->io->write(sprintf('<warning>%s</warning>', trim($e->getMessage(), "\n ")));
                    }

                    if (getenv(Environment::EXIT_ON_FAIL)) {
                        break;
                    }

                    $this->io->write('   <error>Could not apply patch! Skipping.</error>');
                }
            }

            $this->io->write('');

            if ($allPackagePatchesApplied) {
                $this->patchReport->generate($patchesForPackage, $packageInstallPath);
            }

            ksort($extra);
            $package->setExtra($extra);
            $packagesUpdated = true;

            if (getenv(Environment::EXIT_ON_FAIL) && !$allPackagePatchesApplied) {
                break;
            }
        }

        if ($packagesUpdated) {
            $packageRepository->write();
            $this->io->write('<info>Writing updated patch info to lock file</info>');
        }

        if (getenv(Environment::EXIT_ON_FAIL)
            && isset($allPackagePatchesApplied, $relativePath, $patchComment) && !$allPackagePatchesApplied
        ) {
            throw new \Exception(sprintf('Failed to apply %s (%s)!', $relativePath, $patchComment));
        }
    }
}
