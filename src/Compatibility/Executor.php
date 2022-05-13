<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Compatibility;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Composer\Repository\WritableRepositoryInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Installer\InstallationManager;

class Executor
{
    public function repositoryWrite($repository, $installationManager, $isDevMode)
    {
        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            $repository->write();
            return;
        }

        $repository->write($isDevMode, $installationManager);
    }

    public function downloadPackage($downloader, $package, $source, $destDir, $errorHandler, &$patchData, &$errors)
    {
        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            $downloader->download($package, $destDir, false);
            return;
        }

        $resultPromise = $downloader->download($package, $destDir, null, false);
        $resultPromise->then(function ($path) use (&$patchData) {
            $patchData[PatchDefinition::PATH] = $path;
        }, function (\Exception $exception) use ($source, $errorHandler, &$patchData, &$errors) {
            try {
                if (!$exception instanceof \Composer\Downloader\TransportException) {
                    throw $exception;
                }
                $patchData[PatchDefinition::STATUS_LABEL] = $errorHandler->handleError($source, $exception);
            } catch (\Exception $error) {
                $errors[] = $error;
                throw $error;
            }
            $patchData[PatchDefinition::STATUS] = PatchDefinition::STATUS_ERRORS;
        });
    }

    public function assignTmpPathForPatchData(&$patchData, $path)
    {
        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            $patchData[PatchDefinition::PATH] = $path;
        }
    }

    public function waitDownloadCompletion(\Composer\Composer $composer)
    {
        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            return;
        }

        $composer->getLoop()->getHttpDownloader()->wait();
    }

    public function waitForCompletion(\Composer\Composer $composer, array $processes)
    {
        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            return;
        }

        $composer->getLoop()->wait($processes);
    }

    public function processReinstallOperation(
        WritableRepositoryInterface $repository,
        InstallationManager $installationManager,
        InstallOperation $installOperation,
        UninstallOperation $uninstallOperation
    ) {
        if (version_compare(\Composer\Composer::VERSION, '2.0', '<')) {
            return $installationManager->install($repository, $installOperation);
        }

        $package = $installOperation->getPackage();
        $installer = $installationManager->getInstaller($package->getType());

        return $installationManager
            ->uninstall($repository, $uninstallOperation)
            ->then(function()  use ($installer, $package) {
                $installer->download($package);
            })
            ->then(function () use ($installationManager, $installOperation, $repository) {
                $installationManager->install($repository, $installOperation);
            });
    }
}
