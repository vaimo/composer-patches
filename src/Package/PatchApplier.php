<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package;

use Composer\Package\PackageInterface;
use Vaimo\ComposerPatches\Patch\Event;
use Vaimo\ComposerPatches\Events;
use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PatchApplier
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
     * @param \Composer\EventDispatcher\EventDispatcher $eventDispatcher
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

    public function applyPatches(PackageInterface $package, array $patchesQueue)
    {
        $installPath = $this->packageInfoResolver->getSourcePath($package);
        
        if ($installPath == \Vaimo\ComposerPatches\Package\InfoResolver::DEFAULT_PATH) {
            $installPath = $this->vendorRoot;
        }
        
        $appliedPatches = array();

        foreach ($patchesQueue as $source => $patchInfo) {
            $absolutePatchPath = $this->vendorRoot . DIRECTORY_SEPARATOR . $source;
            $relativePath = $source;

            $description = $patchInfo[PatchDefinition::LABEL];
            $config = $patchInfo[PatchDefinition::CONFIG];

            $patchSourceLabel = sprintf('<info>project</info>: %s', $source);
            $patchComment = strtok($description, ',');

            if (file_exists($absolutePatchPath)) {
                $patchSourceLabel = vsprintf(
                    '<info>%s</info>: %s',
                    array(
                        $ownerName = implode(
                            DIRECTORY_SEPARATOR, 
                            array_slice(explode(DIRECTORY_SEPARATOR, $source), 0, 2)
                        ),
                        trim(
                            substr($source, strlen($ownerName)), 
                            DIRECTORY_SEPARATOR
                        )
                    )
                );

                $source = $absolutePatchPath;
            }
            
            $this->logger->writeRaw('%s', array($patchSourceLabel));

            $loggerIndentation = $this->logger->push();

            $this->logger->writeRaw('<comment>%s</comment>', array($patchComment));

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
            } catch (\Exception $exception) {
                $this->logger->writeException($exception);
                
                $this->failureHandler->execute(
                    $exception->getMessage(),
                    $relativePath
                );
            } finally {
                $this->logger->reset($loggerIndentation);
            }
        }

        return $appliedPatches;
    }
}
