<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Managers;

class LockerManager
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $appIO;

    /**
     * @param \Composer\IO\IOInterface $appIO
     */
    public function __construct(
        \Composer\IO\IOInterface $appIO
    ) {
        $this->appIO = $appIO;
    }

    public function readLockData()
    {
        $lockFile = $this->getLockFile();

        if (!$lockFile->exists()) {
            return null;
        }

        return $lockFile->read();
    }

    public function writeLockData(array $lock)
    {
        $lockFile = $this->getLockFile();

        if (!$lockFile->exists()) {
            return;
        }

        $lockFile->write($lock);
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return \Composer\Json\JsonFile
     */
    private function getLockFile()
    {
        $composerFile = \Composer\Factory::getComposerFile();

        $configExtension = 'json';
        $lockExtension = 'lock';

        $lockFile = $configExtension === pathinfo($composerFile, PATHINFO_EXTENSION)
            ? substr($composerFile, 0, -4) . $lockExtension
            : sprintf('%s.%s', $composerFile, $lockExtension);

        return new \Composer\Json\JsonFile($lockFile, null, $this->appIO);
    }
}
