<?php
namespace Vaimo\ComposerPatches\Managers;

use Symfony\Component\Console\Output\OutputInterface;

use Vaimo\ComposerPatches\Composer\ResetOperation;
use Composer\Repository\WritableRepositoryInterface;

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
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface
     */
    private $packagesResolver;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Composer\Package\RootPackageInterface $rootPackage
     * @param \Vaimo\ComposerPatches\Managers\PatchesManager $patchesManager
     * @param \Vaimo\ComposerPatches\Managers\PackagesManager $packagesManager
     * @param \Vaimo\ComposerPatches\Patch\Config $patchConfig
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        \Composer\Package\RootPackageInterface $rootPackage,
        \Vaimo\ComposerPatches\Patch\Config $patchConfig,
        \Vaimo\ComposerPatches\Managers\PatchesManager $patchesManager,
        \Vaimo\ComposerPatches\Managers\PackagesManager $packagesManager,
        \Vaimo\ComposerPatches\Interfaces\PatchPackagesResolverInterface $packagesResolver,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->installationManager = $installationManager;
        $this->rootPackage = $rootPackage;
        $this->patchesManager = $patchesManager;
        $this->packagesManager = $packagesManager;
        $this->packagesResolver = $packagesResolver;
        $this->logger = $logger;
        
        $this->config = $patchConfig;
        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }

    public function processRepository(WritableRepositoryInterface $repository, array $targetFlags = [], $nameFilter = '') 
    {
        $packagesByName = $this->packagesManager->getPackagesByName(
            $repository->getPackages()
        );

        $patches = array();
        
        if ($this->config->isPatchingEnabled()) {
            $sourcePackages = $packagesByName;
            
            if (!$this->config->isPackageScopeEnabled()) {
                $sourcePackages = array(
                    $this->rootPackage->getName() => $this->rootPackage
                );
            }
            
            $patches = $this->packagesManager->getPatches($sourcePackages);
        }
        
        if ($nameFilter) {
            $patches = array_map(function ($group) use ($nameFilter) {
                $keys = array_filter(array_keys($group), function ($path) use ($nameFilter) {
                    return strstr($path, $nameFilter) !== false;
                });

                return array_intersect_key($group, array_flip($keys));
            }, $patches);
        }
        
        $groupedPatches = $this->packageUtils->groupPatchesByTarget($patches);
        
        $resetFlags = array_fill_keys(
            $this->packagesResolver->resolve($groupedPatches, $packagesByName), 
            false
        );
        
        if ($targetFlags) {
            $targetsToKeep = array_filter($targetFlags);
            $targetsToReset = array_keys(
                array_diff_key($targetsToKeep, $targetsToKeep)
            );
            
            $groupedPatches = array_intersect_key($groupedPatches, $targetsToKeep);
            
            $patches = array_replace(
                $patches, 
                array_fill_keys($targetsToReset, array())
            );
            
            $resetFlags = array_intersect_key($resetFlags, $targetsToKeep);
        }
        
        $packagesUpdated = false;

        if ($resetFlags || $patches) {
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
                if (!isset($resetFlags[$target])) {
                    continue;
                }

                if ($resetFlags[$target] === true) {
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
                $resetFlags[$target] = true;
            }
            
            if (!$hasPatches) {
                continue;
            }
            
            $patchesForPackage = $patches[$packageName];

            $hasPatchChanges = false;
            foreach ($patchGroupTargets as $target) {
                $patchesForTarget = isset($groupedPatches[$target]) ? $groupedPatches[$target] : array();

                $hasPatchChanges = $hasPatchChanges 
                    || $this->packageUtils->hasPatchChanges($packagesByName[$target], $patchesForTarget);
            }

            if (!$hasPatchChanges) {
                continue;
            }

            $packagesUpdated = true;

            $this->logger->writeRaw(
                '  - Applying patches for <info>%s</info> (%s)', 
                array($packageName, count($patchesForPackage))
            );

            $installPath = !$package instanceof \Composer\Package\RootPackage
                ? $this->installationManager->getInstallPath($package)
                : '';
            
            try {
                $appliedPatches = $this->patchesManager->applyPatches(
                    $patchesForPackage,
                    $package,
                    $installPath
                );

                $this->patchesManager->registerAppliedPatches(
                    $appliedPatches, 
                    $packagesByName
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
