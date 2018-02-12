<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Config;

use Tivie\OS;

class Context
{
    /**
     * @var OS\Detector
     */
    protected $osDetector;

    public function __construct()
    {
        $this->osDetector = new OS\Detector();
    }
    
    public function getOperationSystemName()
    {
        $typeId = $this->osDetector->getType();

        $labels = array(
            OS\MACOSX => 'mac',
            OS\GEN_UNIX => 'unix',
            OS\BSD => 'bsd',
            OS\LINUX => 'linux',
            OS\WINDOWS => 'windows',
            OS\SUN_OS => 'sun',
            OS\CYGWIN => 'cygwin',
            OS\CYGWIN => 'mingw'
        );

        if (isset($labels[$typeId])) {
            return $labels[$typeId];
        }

        return '';
    }

    public function getOperationSystemFamily()
    {
        $familyId = $this->osDetector->getFamily();

        $labels = array(
            OS\UNIX_FAMILY => 'unix',
            OS\WINDOWS_FAMILY => 'windows',
            OS\UNIX_ON_WINDOWS_FAMILY => 'windows-unix'
        );

        if (isset($labels[$familyId])) {
            return $labels[$familyId];
        }

        return '';
    }
}
