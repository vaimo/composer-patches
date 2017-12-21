<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Package\PackageInterface;

use Vaimo\ComposerPatches\Patch\Event;
use Vaimo\ComposerPatches\Environment;
use Vaimo\ComposerPatches\Events;

class PatchesManager
{
    /**
     * @var \Composer\EventDispatcher\EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var \Composer\Util\RemoteFilesystem
     */
    private $downloader;

    /**
     * @var
     */
    private $failureHandler;
    
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Vaimo\ComposerPatches\Utils\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Applier
     */
    private $patchApplier;

    /**
     * @var string
     */
    private $vendorRoot;

    /**
     * @param \Composer\EventDispatcher\EventDispatcher $eventDispatcher
     * @param \Composer\Util\RemoteFilesystem $downloader
     * @param \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param \Vaimo\ComposerPatches\Patch\Applier $patchApplier
     * @param string $vendorRoot
     */
    public function __construct(
        \Composer\EventDispatcher\EventDispatcher $eventDispatcher,
        \Composer\Util\RemoteFilesystem $downloader,
        \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler,
        \Vaimo\ComposerPatches\Logger $logger,
        \Vaimo\ComposerPatches\Patch\Applier $patchApplier,
        $vendorRoot
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->downloader = $downloader;
        $this->failureHandler = $failureHandler;
        $this->logger = $logger;
        $this->patchApplier = $patchApplier;
        $this->vendorRoot = $vendorRoot;

        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }
    
    public function applyPatches(array $patches, PackageInterface $package, $installPath)
    {
        $appliedPatches = array();

        foreach ($patches as $source => $patchInfo) {
            $absolutePatchPath = $this->vendorRoot . '/' . $source;
            $relativePath = $source;

            $description = $patchInfo['label'];

            $patchSourceLabel = sprintf('<info>%s</info>', $source);
            $patchComment = strtok($description, ',');

            if (file_exists($absolutePatchPath)) {
                $patchSourceLabel = vsprintf(
                    '<info>%s: %s</info>',
                    $this->packageUtils->extractPackageFromVendorPath($source)
                );

                $source = $absolutePatchPath;
            }

            $this->logger->writeRaw('    ~ %s', array($patchSourceLabel));
            $this->logger->writeRaw('      <comment>%s</comment>', array($patchComment));

            try {
                $this->eventDispatcher->dispatch(
                    Events::PRE_APPLY,
                    new Event(Events::PRE_APPLY, $package, $source, $description)
                );

                if (file_exists($source)) {
                    $filename = realpath($source);
                } else {
                    $filename = uniqid('/tmp/') . '.patch';
                    $hostname = parse_url($source, PHP_URL_HOST);

                    $this->downloader->copy($hostname, $source, $filename, false);
                }

                $this->patchApplier->applyFile($filename, $installPath);

                if (isset($hostname)) {
                    unset($hostname);
                    unlink($filename);
                }
                
                $this->eventDispatcher->dispatch(
                    Events::POST_APPLY,
                    new Event(Events::POST_APPLY, $package, $source, $description)
                );

                $appliedPatches[$relativePath] = $patchInfo;
            } catch (\Exception $e) {
                $this->logger->writeException($e);
                $this->failureHandler->execute(
                    sprintf('Failed to apply %s (%s)!', $relativePath, $patchComment)
                );
            }
        }

        return $appliedPatches;
    }

    public function registerAppliedPatches(array $patches, array $packages)
    {
        $affectedPackages = array();
        
        foreach ($patches as $source => $patchInfo) {
            foreach ($patchInfo['targets'] as $target) {
                $affectedPackages[] = $packages[$target];

                $this->packageUtils->registerPatch(
                    $packages[$target],
                    $source,
                    $patchInfo['label']
                );
            }
        }

        foreach ($affectedPackages as $targetPackage) {
            $this->packageUtils->sortPatches($targetPackage);
        }
    }
}
