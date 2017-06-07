<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;

use Vaimo\ComposerPatches\Composer\ResetOperation;
use Vaimo\ComposerPatches\Environment;

class RepositoryManager
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var \Composer\Package\RootPackageInterface
     */
    private $rootPackage;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Vaimo\ComposerPatches\Managers\PatchesManager
     */
    private $patchesManager;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Config
     */
    private $config;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Collector
     */
    private $patchesCollector;

    /**
     * @var \Vaimo\ComposerPatches\Patch\PathNormalizer
     */
    private $patchPathNormalizer;

    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionsProcessor
     */
    private $patchDefinitionsProcessor;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Constraints
     */
    private $patchConstraints;

    /**
     * @var \Vaimo\ComposerPatches\Patch\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Patch\PackagesResolver
     */
    private $packagesResolver;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Composer\Package\RootPackageInterface $rootPackage
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param PatchesManager $patchesManager
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        \Composer\Package\RootPackageInterface $rootPackage,
        \Vaimo\ComposerPatches\Logger $logger,
        \Vaimo\ComposerPatches\Managers\PatchesManager $patchesManager
    ) {
        $this->installationManager = $installationManager;
        $this->rootPackage = $rootPackage;
        $this->logger = $logger;
        $this->patchesManager = $patchesManager;

        $extraInfo = $this->rootPackage->getExtra();

        $this->config = new \Vaimo\ComposerPatches\Patch\Config($extraInfo);

        $this->patchesCollector = new \Vaimo\ComposerPatches\Patch\Collector();
        $this->patchPathNormalizer = new \Vaimo\ComposerPatches\Patch\PathNormalizer($installationManager);
        $this->patchDefinitionsProcessor = new \Vaimo\ComposerPatches\Patch\DefinitionsProcessor();
        $this->patchConstraints = new \Vaimo\ComposerPatches\Patch\Constraints($extraInfo);
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
        $this->packagesResolver = new \Vaimo\ComposerPatches\Patch\PackagesResolver();
    }

    public function processRepository(WritableRepositoryInterface $repository, $vendorDir)
    {
        $packages = $repository->getPackages();

        if ($patchingEnabled = $this->config->isPatchingEnabled()) {
            $patches = $this->patchesCollector->gatherAllPatches(
                array_merge($packages, [$this->rootPackage])
            );

            $patches = $this->patchConstraints->apply($patches, $packages);
            $patches = $this->patchPathNormalizer->process($patches, $packages, $vendorDir);
            $patches = $this->patchDefinitionsProcessor->validate($patches, $vendorDir);
            $patches = $this->patchDefinitionsProcessor->flatten($patches);
        } else {
            $patches = array();
        }

        $packageResetFlags = array_fill_keys(
            !getenv(Environment::FORCE_REAPPLY) || !$patchingEnabled
                ? $this->packagesResolver->resolvePackagesToReinstall($packages, $patches)
                : array_keys($patches),
            true
        );

        $packagesUpdated = false;

        foreach ($packages as $package) {
            $packageName = $package->getName();

            if (isset($packageResetFlags[$packageName])) {
                $this->logger->writeNewLine();

                $this->installationManager->install($repository, new ResetOperation(
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

            $packagesUpdated = true;

            $this->logger->writeRaw('  - Applying patches for <info>%s</info>', array($packageName));

            try {
                $this->patchesManager->processPatches(
                    $patchesForPackage,
                    $package,
                    $this->installationManager->getInstallPath($package),
                    $vendorDir
                );
            } catch (\Vaimo\ComposerPatches\Exceptions\PatchFailureException $e) {
                $repository->write();

                throw $e;
            }
        }

        if (!$packagesUpdated) {
            return;
        }

        $this->logger->writeNewLine();
        $this->logger->write('Writing updated patch info to lock file', 'info');

        $repository->write();
    }
}
