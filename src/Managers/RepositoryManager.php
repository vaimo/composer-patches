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
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Composer\Package\RootPackageInterface $rootPackage
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param PatchesManager $patchesManager
     * @param array $loaders
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        \Composer\Package\RootPackageInterface $rootPackage,
        \Vaimo\ComposerPatches\Managers\PatchesManager $patchesManager,
        \Vaimo\ComposerPatches\Logger $logger,
        array $loaders
    ) {
        $this->installationManager = $installationManager;
        $this->rootPackage = $rootPackage;
        $this->logger = $logger;
        $this->patchesManager = $patchesManager;

        $extraInfo = $this->rootPackage->getExtra();

        $this->config = new \Vaimo\ComposerPatches\Patch\Config($extraInfo);

        $this->patchesCollector = new \Vaimo\ComposerPatches\Patch\Collector($loaders);

        $this->patchPathNormalizer = new \Vaimo\ComposerPatches\Patch\PathNormalizer($installationManager);
        $this->patchDefinitionsProcessor = new \Vaimo\ComposerPatches\Patch\DefinitionsProcessor();
        $this->patchConstraints = new \Vaimo\ComposerPatches\Patch\Constraints($extraInfo);
        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();
        $this->packagesResolver = new \Vaimo\ComposerPatches\Patch\PackagesResolver();
    }

    public function processRepository(
        WritableRepositoryInterface $repository, $vendorRoot
    ) {
        $packages = $repository->getPackages();

        $packagesByName = array();

        foreach ($packages as $package) {
            if ($package instanceof \Composer\Package\AliasPackage) {
                $package = $package->getAliasOf();
            }

            $packagesByName[$package->getName()] = $package;
        }

        $packagesByName[$this->rootPackage->getName()] = $this->rootPackage;

        if ($patchingEnabled = $this->config->isPatchingEnabled()) {
            $patches = $this->patchesCollector->collect(array_merge(
                $this->config->isPackageScopeEnabled() ? $packages : array(),
                array($this->rootPackage)
            ));

            if (isset($patches['*'])) {
                $rootName = $this->rootPackage->getName();

                if (!isset($patches[$rootName])) {
                    $patches[$rootName] = array();
                }

                $patches[$rootName] = array_merge($patches[$rootName], $patches['*']);
                unset($patches['*']);
            }

            $patches = $this->patchPathNormalizer->process($patches, $packagesByName, $vendorRoot);
            $patches = $this->patchConstraints->apply($patches, $packagesByName);
            $patches = $this->patchDefinitionsProcessor->validate($patches, $vendorRoot);
            $patches = $this->patchDefinitionsProcessor->simplify($patches);
        } else {
            $patches = array();
        }

        $groupedPatches = $this->packageUtils->groupPatchesByTarget($patches);

        $packagesToReset = array_merge(
            $this->packagesResolver->resolvePackagesToReinstall($packagesByName, $groupedPatches),
            getenv(Environment::FORCE_REAPPLY) ? array_keys($groupedPatches) : array()
        );

        $packageResetFlags = array_fill_keys($packagesToReset, false);
        $packagesUpdated = false;

        if ($packagesToReset || $patches) {
            $this->logger->write('Processing patches configuration', 'info');
        }

        foreach ($packagesByName as $packageName => $package) {
            $hasPatches = !empty($patches[$packageName]);

            $patchGroupTargets = array();

            if ($hasPatches) {
                foreach ($patches[$packageName] as $patch) {
                    $patchGroupTargets = array_merge($patchGroupTargets, $patch['targets']);
                }

                $patchGroupTargets = array_unique($patchGroupTargets);
            } else {
                $patchGroupTargets = array($packageName);
            }

            foreach ($patchGroupTargets as $target) {
                if (!isset($packageResetFlags[$target])) {
                    continue;
                }

                if ($packageResetFlags[$target] === true) {
                    continue;
                }

                if (!$hasPatches && !isset($groupedPatches[$target])) {
                    $this->logger->writeRaw(
                        '  - Resetting patched package <info>%s</info>', array($target)
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
                $packageResetFlags[$target] = true;
            }

            if (!$hasPatches) {
                continue;
            }

            $patchesForPackage = $patches[$packageName];

            $hasPatchChanges = false;
            foreach ($patchGroupTargets as $target) {
                $hasPatchChanges = $hasPatchChanges || $this->packageUtils->hasPatchChanges(
                        $packagesByName[$target],
                        $groupedPatches[$target]
                    );
            }

            if (!$hasPatchChanges) {
                continue;
            }

            $packagesUpdated = true;

            $this->logger->writeRaw('  - Applying patches for <info>%s</info>', array($packageName));

            try {
                $appliedPatches = $this->patchesManager->processPatches(
                    $patchesForPackage,
                    $package,
                    !$package instanceof \Composer\Package\RootPackage 
                        ? $this->installationManager->getInstallPath($package) 
                        : '',
                    $vendorRoot
                );

                $targetedPackages = array();
                
                foreach ($appliedPatches as $source => $patchInfo) {
                    foreach ($patchInfo['targets'] as $target) {
                        $targetedPackages[] = $packagesByName[$target];

                        $this->packageUtils->registerPatch(
                            $packagesByName[$target],
                            $source,
                            $patchInfo['label']
                        );
                    }
                }

                foreach ($targetedPackages as $targetPackage) {
                    $this->packageUtils->sortPatches($targetPackage);
                }
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
