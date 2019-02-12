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
    private $io;

    /**
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\IO\IOInterface $io
    ) {
        $this->io = $io;
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

    private function getLockFile()
    {
        $composerFile = \Composer\Factory::getComposerFile();

        $lockFile = 'json' === pathinfo($composerFile, PATHINFO_EXTENSION)
            ? substr($composerFile, 0, -4) . 'lock'
            : $composerFile . '.lock';

        return new \Composer\Json\JsonFile($lockFile, null, $this->io);
    }
}
