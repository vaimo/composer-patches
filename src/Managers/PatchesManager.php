<?php
namespace Vaimo\ComposerPatches\Managers;

use Composer\Package\PackageInterface;

use Vaimo\ComposerPatches\Patch\Event;
use Vaimo\ComposerPatches\Events;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PatchesManager
{
    /**
     * @var \Vaimo\ComposerPatches\Package\InfoResolver
     */
    private $packageInfoResolver;
    
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
     * @param \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver
     * @param \Composer\Util\RemoteFilesystem $downloader
     * @param \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param \Vaimo\ComposerPatches\Patch\Applier $patchApplier
     * @param string $vendorRoot
     */
    public function __construct(
        \Vaimo\ComposerPatches\Package\InfoResolver $packageInfoResolver,
        \Composer\EventDispatcher\EventDispatcher $eventDispatcher,
        \Composer\Util\RemoteFilesystem $downloader,
        \Vaimo\ComposerPatches\Interfaces\PatchFailureHandlerInterface $failureHandler,
        \Vaimo\ComposerPatches\Logger $logger,
        \Vaimo\ComposerPatches\Patch\Applier $patchApplier,
        $vendorRoot
    ) {
        $this->packageInfoResolver = $packageInfoResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->downloader = $downloader;
        $this->failureHandler = $failureHandler;
        $this->logger = $logger;
        $this->patchApplier = $patchApplier;
        $this->vendorRoot = $vendorRoot;

        $this->packageUtils = new \Vaimo\ComposerPatches\Utils\PackageUtils();
    }
    
    public function applyPatches(PackageInterface $package, array $patches)
    {
        $installPath = $this->packageInfoResolver->getSourcePath($package);
        
        $appliedPatches = array();
        
        foreach ($patches as $source => $patchInfo) {
            $absolutePatchPath = $this->vendorRoot . '/' . $source;
            $relativePath = $source;

            $description = $patchInfo[PatchDefinition::LABEL];
            $config = $patchInfo[PatchDefinition::CONFIG];
            
            $patchSourceLabel = sprintf('<info>project</info>: %s', $source);
            $patchComment = strtok($description, ',');

            if (file_exists($absolutePatchPath)) {
                $patchSourceLabel = vsprintf(
                    '<info>%s</info>: %s',
                    array(
                        $ownerName = implode('/', array_slice(explode('/', $source), 0, 2)),
                        trim(substr($source, strlen($ownerName)), '/')
                    )
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
                
                $this->patchApplier->applyFile($filename, $installPath, $config);

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

    public function groupPatches(array $patches)
    {
        $patchesByTarget = array();

        foreach ($patches as $patchGroup) {
            foreach ($patchGroup as $patchPath => $patchInfo) {
                foreach ($patchInfo[PatchDefinition::TARGETS] as $target) {
                    if (!isset($patchesByTarget[$target])) {
                        $patchesByTarget[$target] = array();
                    }

                    $patchesByTarget[$target][$patchPath] = $patchInfo[PatchDefinition::LABEL];
                }
            }
        }

        return $patchesByTarget;
    }
}
