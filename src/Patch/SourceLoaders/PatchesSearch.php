<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch\SourceLoaders;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
use Vaimo\ComposerPatches\Config as PluginConfig;

class PatchesSearch implements \Vaimo\ComposerPatches\Interfaces\PatchSourceLoaderInterface
{
    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var bool
     */
    private $devMode;

    /**
     * @var \Vaimo\ComposerPatches\Patch\File\Analyser
     */
    private $fileAnalyser;

    /**
     * @var \Vaimo\ComposerPatches\Patch\File\Header\Parser
     */
    private $patchHeaderParser;

    /**
     * @var \Vaimo\ComposerPatches\Utils\FileSystemUtils
     */
    private $fileSystemUtils;

    /**
     * @var array
     */
    private $tagAliases = array();

    /**
     * @var array 
     */
    private $localTypes;
    
    /**
     * @var array
     */
    private $devModeTypes;

    /**
     * @var array 
     */
    private $bundledModeTypes;

    /**
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param bool $devMode
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        $devMode = false
    ) {
        $this->installationManager = $installationManager;
        $this->devMode = $devMode;

        $this->fileAnalyser = new \Vaimo\ComposerPatches\Patch\File\Analyser();
        $this->patchHeaderParser = new \Vaimo\ComposerPatches\Patch\File\Header\Parser();
        $this->fileSystemUtils = new \Vaimo\ComposerPatches\Utils\FileSystemUtils();

        $this->tagAliases = array(
            PatchDefinition::LABEL => array('desc', 'description', 'reason'),
            PatchDefinition::ISSUE => array('ticket', 'issues', 'tickets'),
            PatchDefinition::VERSION => array('constraint'),
            PatchDefinition::PACKAGE => array('target', 'module', 'targets'),
            PatchDefinition::LINK => array('links', 'reference', 'ref', 'url'),
            PatchDefinition::TYPE => array('mode')
        );

        $this->localTypes = array('local', 'root');
        $this->devModeTypes = array('developer', 'dev', 'development', 'develop');
        $this->bundledModeTypes = array('bundle', 'bundled', 'merged', 'multiple', 'multi', 'group');
    }

    public function load(\Composer\Package\PackageInterface $package, $source)
    {
        if (!is_array($source)) {
            $source = array($source);
        }

        $basePath = $this->getInstallPath($package);
        $basePathLength = strlen($basePath);

        $results = array();

        echo 'Scanning search sources:' . PHP_EOL;
        foreach ($source as $item) {
            echo '> ' . $item . PHP_EOL;
            
            $paths = $this->fileSystemUtils->collectPathsRecursively(
                $basePath . DIRECTORY_SEPARATOR . $item,
                PluginConfig::PATCH_FILE_REGEX_MATCHER
            );

            $groups = array();

            echo 'Search results:' . PHP_EOL;
            
            foreach ($paths as $path) {
                $content = implode(PHP_EOL, file($path));
                
                echo '========================================' . PHP_EOL;
                echo 'PATH: ' . $path . PHP_EOL;
                echo 'CONTENT:' . PHP_EOL;
                echo $content . PHP_EOL;

                $definition = $this->createDefinitionItem($content, array(
                    PatchDefinition::PATH => $path,
                    PatchDefinition::SOURCE => trim(substr($path, $basePathLength), DIRECTORY_SEPARATOR)
                ));
                
                echo '========================================' . PHP_EOL;

                echo json_encode($definition, JSON_PRETTY_PRINT) . PHP_EOL;
                
                if (!isset($definition[PatchDefinition::TARGET])) {
                    continue;
                }

                echo 'RESULT: REGISTERED' . PHP_EOL;
                echo '--------------------------------------------' . PHP_EOL;

                $target = $definition[PatchDefinition::TARGET];

                if (!isset($groups[$target])) {
                    $groups[$target] = array();
                }

                $groups[$target][] = $definition;
            }

            $results[] = $groups;
        }

        return $results;
    }

    private function getInstallPath(\Composer\Package\PackageInterface $package)
    {
        if ($package instanceof \Composer\Package\RootPackage) {
            return getcwd();
        }

        return $this->installationManager->getInstallPath($package);
    }

    private function createDefinitionItem($contents, array $values = array())
    {
        $header = $this->fileAnalyser->getHeader($contents);

        echo 'HEADER:' . PHP_EOL;
        
        $data = $this->applyAliases(
            $this->patchHeaderParser->parseContents($header),
            $this->tagAliases
        );

        echo json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;

        echo '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~' . PHP_EOL;
        
        list($target, $depends, $flags) = $this->resolveBaseInfo($data);

        echo 'TARGET:' . $target . PHP_EOL;
        echo 'DEPENDS:' . implode(',', $depends) . PHP_EOL;
        echo 'FLAGS:' . implode(',' , array_keys($flags)) . PHP_EOL;
        
        if (array_intersect_key($flags, array_flip($this->bundledModeTypes))) {
            $target = PatchDefinition::BUNDLE_TARGET;
        }
        
        if (!$target) {
            return array();
        }

        if (array_intersect_key($flags, array_flip($this->localTypes))) {
            $values[PatchDefinition::LOCAL] = true;
        }
        
        if (!$this->devMode && array_intersect_key($flags, array_flip($this->devModeTypes))) {
            $data[PatchDefinition::SKIP] = true;
        }

        $definition = array_replace(array(
            PatchDefinition::LABEL => implode(
                PHP_EOL,
                isset($data[PatchDefinition::LABEL]) ? $data[PatchDefinition::LABEL] : array('')
            ),
            PatchDefinition::TARGET => $target,
            PatchDefinition::CWD => $this->extractSingleValue($data, PatchDefinition::CWD),
            PatchDefinition::TARGETS => $this->extractValueList(
                $data, 
                PatchDefinition::TARGETS, 
                array($target)
            ),
            PatchDefinition::DEPENDS => $depends,
            PatchDefinition::SKIP => isset($data[PatchDefinition::SKIP]),
            PatchDefinition::AFTER => $this->extractValueList($data, PatchDefinition::AFTER),
            PatchDefinition::ISSUE => $this->extractSingleValue($data, PatchDefinition::ISSUE),
            PatchDefinition::LINK => $this->extractSingleValue($data, PatchDefinition::LINK),
            PatchDefinition::LEVEL => $this->extractSingleValue($data, PatchDefinition::LEVEL),
            PatchDefinition::CATEGORY => $this->extractSingleValue($data, PatchDefinition::CATEGORY)
        ), $values);

        return $definition;
    }

    private function resolveBaseInfo(array $data)
    {
        $target = false;

        $package = $this->extractSingleValue($data, PatchDefinition::PACKAGE);
        $depends = $this->extractSingleValue($data, PatchDefinition::DEPENDS);
        $version = $this->extractSingleValue($data, PatchDefinition::VERSION, '>=0.0.0');

        if (strpos($version, ':') !== false) {
            $valueParts = explode(':', $version);

            $depends = trim(array_shift($valueParts));
            $version = trim(implode(':', $valueParts));
        }

        if (strpos($package, ':') !== false) {
            $valueParts = explode(':', $package);

            $package = trim(array_shift($valueParts));
            $version = trim(implode(':', $valueParts));
        }

        if (!$target && $package) {
            $target = $package;
        }

        if (!$target && $depends) {
            $target = $depends;
        }

        if (!$depends && $target) {
            $depends = $target;
        }

        $dependsConfig = array_reduce(
            array_map(function ($item) {
                $valueParts = explode(':', $item);
                
                return array(
                    trim(array_shift($valueParts)) => trim(implode(':', $valueParts)) ?: '>=0.0.0'
                );
            }, array_merge(
                array($depends . ':' . $version), 
                $this->extractValueList($data, PatchDefinition::DEPENDS))
            ),
            'array_replace',
            array()
        );

        $patchTypeFlags = array_fill_keys(
            explode(
                PatchDefinition::TYPE_SEPARATOR,
                $this->extractSingleValue($data, PatchDefinition::TYPE)
            ),
            true
        );

        $flags = array_filter(
            array_replace(
                $dependsConfig,
                array(PatchDefinition::BUNDLE_TARGET => false, '' => false)
            )
        );
        
        return array($target, $flags, $patchTypeFlags);
    }

    private function extractValueList(array $data, $name, $default = array())
    {
        return isset($data[$name]) ? array_filter($data[$name]) : $default;
    }

    private function extractSingleValue(array $data, $name, $default = null)
    {
        return isset($data[$name]) ? reset($data[$name]) : $default;
    }

    private function applyAliases(array $data, array $aliases)
    {
        foreach ($aliases as $target => $origins) {
            if (isset($data[$target])) {
                continue;
            }

            foreach ($origins as $origin) {
                if (!isset($data[$origin])) {
                    continue;
                }

                $data[$target] = $data[$origin];
            }
        }

        return $data;
    }
}
