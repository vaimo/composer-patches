<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Repository\Lock;

use Vaimo\ComposerPatches\Composer\ConfigKeys as Config;
use Vaimo\ComposerPatches\Config as PluginConfig;
use Vaimo\ComposerPatches\Composer\Constraint;

class Sanitizer
{
    /**
     * @var \Vaimo\ComposerPatches\Managers\LockerManager
     */
    private $lockerManager;

    /**
     * @var \Vaimo\ComposerPatches\Utils\DataUtils
     */
    private $dataUtils;

    /**
     * @param \Composer\IO\IOInterface $appIO
     */
    public function __construct(
        \Composer\IO\IOInterface $appIO
    ) {
        $this->lockerManager = new \Vaimo\ComposerPatches\Managers\LockerManager($appIO);

        $this->dataUtils = new \Vaimo\ComposerPatches\Utils\DataUtils();
    }

    public function sanitize()
    {
        $lockData = $this->lockerManager->readLockData();

        if (!$lockData) {
            return;
        }

        $queriedPaths = array(
            implode('/', array(Config::PACKAGES, Constraint::ANY)),
            implode('/', array(Config::PACKAGES_DEV, Constraint::ANY))
        );

        $nodes = $this->dataUtils->getNodeReferencesByPaths($lockData, $queriedPaths);

        foreach ($nodes as &$node) {
            if (!isset($node[Config::CONFIG_ROOT][PluginConfig::APPLIED_FLAG])) {
                continue;
            }

            unset($node[Config::CONFIG_ROOT][PluginConfig::APPLIED_FLAG]);

            if ($node[Config::CONFIG_ROOT]) {
                continue;
            }

            unset($node[Config::CONFIG_ROOT]);
        }

        unset($node);

        $this->lockerManager->writeLockData($lockData);
    }
}
