<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class PatchesSearch implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var \Vaimo\ComposerPatches\Package\ConfigReader
     */
    private $configLoader;

    /**
     * @var \Vaimo\ComposerPatches\Patch\File\Analyser
     */
    private $fileAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Patch\File\Header\Parser
     */
    private $patchHeaderParser;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager
    ) {
        $this->installationManager = $installationManager;

        $this->configLoader = new \Vaimo\ComposerPatches\Package\ConfigReader();
        $this->fileAnalyser = new \Vaimo\ComposerPatches\Patch\File\Analyser();
        $this->patchHeaderParser = new \Vaimo\ComposerPatches\Patch\File\Header\Parser();
    }

    public function load(\Composer\Package\PackageInterface $package, $source)
    {
        if (!is_array($source)) {
            $source = array($source);
        }

        if ($package instanceof \Composer\Package\RootPackage) {
            $basePath = getcwd();
        } else {
            $basePath = $this->installationManager->getInstallPath($package);
        }

        $results = array();

        foreach ($source as $item) {
            $rootPath = $basePath . DIRECTORY_SEPARATOR . $item;
            $basePathLength = strlen($basePath);

            $paths = $this->collectPaths($rootPath, '/^.+\.patch/i');

            $groups = array();

            foreach ($paths as $path) {
                $contents = file_get_contents($path);

                $header = $this->fileAnalyser->getHeader($contents);

                $data = $this->patchHeaderParser->parseContents($header);

                $target = isset($data['package']) ? reset($data['package']) : false;

                $depends = isset($data[PatchDefinition::DEPENDS])
                    ? reset($data[PatchDefinition::DEPENDS])
                    : false;

                $version = isset($data[PatchDefinition::VERSION])
                    ? reset($data[PatchDefinition::VERSION])
                    : '>=0.0.0';

                if (strpos($version, ':') !== false) {
                    $versionParts = explode(':', $version);
                    $depends = trim(array_shift($versionParts));
                    $version = trim(implode(':', $versionParts));
                }

                if (!$target && $depends) {
                    $target = $depends;
                }

                if (!$depends && $target) {
                    $depends = $target;
                }

                if (!$target) {
                    continue;
                }

                if (!isset($groups[$target])) {
                    $groups[$target] = array();
                }

                $groups[$target][] = array(
                    PatchDefinition::LABEL => implode(
                        PHP_EOL,
                        isset($data[PatchDefinition::LABEL]) ? $data[PatchDefinition::LABEL] : array('')
                    ),
                    PatchDefinition::DEPENDS => array($depends => $version),
                    PatchDefinition::PATH => $path,
                    PatchDefinition::SOURCE => trim(substr($path, $basePathLength), '/'),
                    PatchDefinition::SKIP => isset($data[PatchDefinition::SKIP]),
                    PatchDefinition::AFTER => isset($data[PatchDefinition::AFTER])
                        ? array_filter($data[PatchDefinition::AFTER])
                        : array(),
                    PatchDefinition::ISSUE => isset($data[PatchDefinition::ISSUE])
                        ? reset($data[PatchDefinition::ISSUE])
                        : (isset($data['ticket'])
                            ? reset($data['ticket'])
                            : false
                        ),
                    PatchDefinition::LINK => isset($data[PatchDefinition::LINK])
                        ? reset($data[PatchDefinition::LINK])
                        : (isset($data['links'])
                            ? reset($data['links'])
                            : false
                        )
                );
            }

            $results[] = $groups;
        }

        return $results;
    }

    private function collectPaths($rootPath, $pattern)
    {
        $directory = new \RecursiveDirectoryIterator($rootPath);
        $iterator = new \RecursiveIteratorIterator($directory);

        $regexIterator = new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

        $files = array();

        foreach ($regexIterator as $info) {
            $files[] = reset($info);
        }

        return $files;
    }
}
