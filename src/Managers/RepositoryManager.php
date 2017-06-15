<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Repository\WritableRepositoryInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Vaimo\ComposerPatches\Composer\ResetOperation;
use Vaimo\ComposerPatches\Environment;

use Vaimo\ComposerPatches\Composer\OutputUtils;

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
     * @var array
     */
    private $appliedPatches = array();

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

        $this->patchesCollector = new \Vaimo\ComposerPatches\Patch\Collector(array(
            'patches' => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchList(),
            'patches-file' => new \Vaimo\ComposerPatches\Patch\SourceLoaders\PatchesFile()
        ));

        $this->patchPathNormalizer = new \Vaimo\ComposerPatches\Patch\PathNormalizer($installationManager);
        $this->patchDefinitionsProcessor = new \Vaimo\ComposerPatches\Patch\DefinitionsProcessor();
        $this->patchConstraints = new \Vaimo\ComposerPatches\Patch\Constraints($extraInfo);
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
        $this->packagesResolver = new \Vaimo\ComposerPatches\Patch\PackagesResolver();
    }

    public function processRepository(WritableRepositoryInterface $repository, $vendorRoot)
    {
        $packages = $repository->getPackages();

        if ($patchingEnabled = $this->config->isPatchingEnabled()) {
            $patches = $this->patchesCollector->collect(array_merge(
                $this->config->isPackageScopeEnabled() ? $packages : array(),
                array($this->rootPackage)
            ));

            $packagesByName = array();
            foreach ($packages as $package) {
                $packagesByName[$package->getName()] = $package;
            }

            $patches = $this->patchPathNormalizer->process($patches, $packagesByName, $vendorRoot);

            $patches = $this->patchConstraints->apply($patches, $packagesByName);
            $patches = $this->patchDefinitionsProcessor->validate($patches, $vendorRoot);
            $patches = $this->patchDefinitionsProcessor->flatten($patches);
        } else {
            $patches = array();
        }

        $packagesToReset = array_merge(
            $this->packagesResolver->resolvePackagesToReinstall($packages, $patches),
            getenv(Environment::FORCE_REAPPLY) ? array_keys($patches) : array()
        );

        $packageResetFlags = array_fill_keys($packagesToReset, true);
        $packagesUpdated = false;

        if ($packagesToReset || $patches) {
            $this->logger->write('Processing patches configuration', 'info');
        }

        foreach ($packages as $package) {
            $packageName = $package->getName();
            $hasPatches = !empty($patches[$packageName]);

            if (isset($packageResetFlags[$packageName])) {
                if (!$hasPatches) {
                    $this->logger->writeRaw(
                        '  - Resetting patched package <info>%s</info>', array($packageName)
                    );
                }

                $output = $this->logger->getOutputInstance();

                $verbosityLevel = OutputUtils::resetVerbosity($output, OutputInterface::VERBOSITY_QUIET);

                try {
                    $this->installationManager->install(
                        $repository,
                        new ResetOperation($package, 'Package reset due to changes in patches configuration')
                    );

                    OutputUtils::resetVerbosity($output, $verbosityLevel);
                } catch (\Exception $e) {
                    OutputUtils::resetVerbosity($output, $verbosityLevel);

                    throw $e;
                }

                $packagesUpdated = $this->packageUtils->resetAppliedPatches($package);
            }

            if (!$hasPatches) {
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
                    $vendorRoot
                );
            } catch (\Vaimo\ComposerPatches\Exceptions\PatchFailureException $e) {
                $repository->write();

                throw $e;
            }

            $this->logger->writeNewLine();
        }

        if (!$packagesUpdated) {
            $this->logger->writeRaw('Nothing to patch');
        }

        $this->logger->write('Writing patch info to install file', 'info');

        $repository->write();
    }
}
