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

        $dataChanged = false;

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
            $dataChanged = true;

            if ($node[Config::CONFIG_ROOT]) {
                continue;
            }

            unset($node[Config::CONFIG_ROOT]);
        }

        unset($node);

        if (!$dataChanged) {
            return;
        }

        $lockData = $this->fixupJsonDataType($lockData);
        $this->lockerManager->writeLockData($lockData);
    }

    /**
     * Copy-paste from composer/composer/src/Composer/Package/Locker.php. Would prefer to use the locker directly,
     * but there's no alternative to src/Managers/LockerManager::writeLockData(). The method setLockData() is really
     * close, but it seems to expect some of the data to be objects, which we don't have in this context.
     *
     * @param mixed[] $lockData
     *
     * @return mixed[]
     */
    private function fixupJsonDataType(array $lockData)
    {
        foreach (['stability-flags', 'platform', 'platform-dev'] as $key) {
            if (isset($lockData[$key]) && is_array($lockData[$key]) && \count($lockData[$key]) === 0) {
                $lockData[$key] = new \stdClass();
            }
        }

        if (is_array($lockData['stability-flags'])) {
            ksort($lockData['stability-flags']);
        }

        return $lockData;
    }
}
