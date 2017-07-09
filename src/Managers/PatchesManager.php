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
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;

    /**
     * @var \Vaimo\ComposerPatches\Patch\PackageUtils
     */
    private $packageUtils;

    /**
     * @var \Composer\Util\RemoteFilesystem
     */
    private $patchDownloader;

    /**
     * @var \Vaimo\ComposerPatches\Patch\Applier
     */
    private $patchApplier;

    /**
     * @param \Composer\EventDispatcher\EventDispatcher $eventDispatcher
     * @param \Composer\Util\RemoteFilesystem $downloader
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Composer\EventDispatcher\EventDispatcher $eventDispatcher,
        \Composer\Util\RemoteFilesystem $downloader,
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->downloader = $downloader;
        $this->logger = $logger;

        $this->packageUtils = new \Vaimo\ComposerPatches\Patch\PackageUtils();

        $this->patchDownloader = $downloader;

        $this->patchApplier = new \Vaimo\ComposerPatches\Patch\Applier($this->logger, array(
            'GIT' => array(
                'validate' => 'git apply --check -p%s %s',
                'patch' => 'git apply -p%s %s'
            ),
            'PATCH' => array(
                'validate' => 'patch -p%s --dry-run --no-backup-if-mismatch < %s',
                'patch' => 'patch -p%s --no-backup-if-mismatch < %s'
            )
        ));
    }

    public function processPatches(array $patches, PackageInterface $package, $installPath, $vendorRoot)
    {
        $appliedPatches = array();

        foreach ($patches as $source => $patchInfo) {
            $description = $patchInfo['label'];
            $relativePath = $source;

            $patchSourceLabel = sprintf('<info>%s</info>', $source);
            $absolutePatchPath = $vendorRoot . '/' . $source;
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

                    $this->patchDownloader->copy($hostname, $source, $filename, false);
                }

                $this->patchApplier->execute($filename, $installPath);

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

                if (getenv(Environment::EXIT_ON_FAIL)) {
                    throw new \Vaimo\ComposerPatches\Exceptions\PatchFailureException(
                        sprintf('Failed to apply %s (%s)!', $relativePath, $patchComment)
                    );
                }

                $this->logger->writeRaw('   <error>Could not apply patch! Skipping.</error>');
            }
        }

        return $appliedPatches;
    }
}
