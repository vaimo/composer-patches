<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

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
                $depends = isset($data['depends']) ? reset($data['depends']) : false;
                $version = isset($data['version']) ? reset($data['version']) : '>=0.0.0';

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
                    'label' => implode(PHP_EOL, isset($data['label']) ? $data['label'] : array('')),
                    'depends' => array($depends => $version),
                    'path' => $path,
                    'source' => trim(substr($path, $basePathLength), '/'),
                    'skip' => isset($data['skip']),
                    'after' => isset($data['after']) ? array_filter($data['after']) : array(),
                    'issue' => isset($data['issue'])
                        ? reset($data['issue'])
                        : (isset($data['ticket'])
                            ? reset($data['ticket'])
                            : false
                        ),
                    'link' => isset($data['link'])
                        ? reset($data['link'])
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
