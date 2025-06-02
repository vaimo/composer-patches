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
     * @var \Vaimo\ComposerPatches\Patch\File\Loader
     */
    private $patchFileLoader;

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
    private $tagAliases;

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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param bool $devMode
     */
    public function __construct(
        \Composer\Installer\InstallationManager $installationManager,
        $devMode = false
    ) {
        $this->installationManager = $installationManager;
        $this->devMode = $devMode;

        $this->patchFileLoader = new \Vaimo\ComposerPatches\Patch\File\Loader();
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
        $results = array();

        foreach ($source as $item) {
            $paths = $this->fileSystemUtils->collectFilePathsRecursively(
                $basePath . DIRECTORY_SEPARATOR . $item,
                sprintf('/%s/i', PluginConfig::PATCH_FILE_REGEX_MATCHER)
            );

            $results[] = $this->createPatchDefinitions($basePath, $paths);
        }

        return $results;
    }

    private function createPatchDefinitions($basePath, array $paths)
    {
        $groups = array();
        $basePathLength = strlen($basePath);

        foreach ($paths as $path) {
            $contents = $this->patchFileLoader->loadWithNormalizedLineEndings($path);

            $definition = $this->createDefinitionItem($contents, array(
                PatchDefinition::PATH => $path,
                PatchDefinition::SOURCE => trim(
                    substr($path, $basePathLength),
                    DIRECTORY_SEPARATOR
                )
            ));

            if (!isset($definition[PatchDefinition::TARGET])) {
                continue;
            }

            $target = $definition[PatchDefinition::TARGET];

            if (!isset($groups[$target])) {
                $groups[$target] = array();
            }

            $groups[$target][] = $definition;
        }

        return $groups;
    }

    private function getInstallPath(\Composer\Package\PackageInterface $package)
    {
        if ($package instanceof \Composer\Package\RootPackageInterface) {
            return getcwd();
        }

        return $this->installationManager->getInstallPath($package);
    }

    private function createDefinitionItem($contents, array $values = array())
    {
        $header = $this->fileAnalyser->getHeader($contents);

        $data = $this->applyAliases(
            $this->patchHeaderParser->parseContents($header),
            $this->tagAliases
        );

        list($target, $depends, $flags) = $this->resolveBaseInfo($data);

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

        return array_replace(array(
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
    }

    private function resolveBaseInfo(array $data)
    {
        $target = false;

        list($package, $version, $depends) = $this->resolveBaseData($data);

        if ($package) {
            $target = $package;
        }

        if (!$target && $depends) {
            $target = $depends;
        }

        if (!$depends && $target) {
            $depends = $target;
        }

        $dependsList = array_merge(
            array(sprintf('%s:%s', $package, $version)),
            array(sprintf('%s:%s', $depends, $version)),
            $this->extractValueList($data, PatchDefinition::DEPENDS)
        );

        $patchTypeFlags = array_fill_keys(
            explode(
                PatchDefinition::TYPE_SEPARATOR,
                $this->extractSingleValue($data, PatchDefinition::TYPE) ?? ''
            ),
            true
        );

        return array(
            $target,
            $this->normalizeDependencies($dependsList),
            $patchTypeFlags
        );
    }

    private function resolveBaseData(array $data)
    {
        $package = $this->extractSingleValue($data, PatchDefinition::PACKAGE, '');
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

        return array($package, $version, $depends);
    }

    private function normalizeDependencies($dependsList)
    {
        $dependsNormalized = array_map(
            function ($item) {
                $valueParts = explode(':', $item);

                return array(
                    trim(array_shift($valueParts) ?? '') => trim(array_shift($valueParts) ?? '') ?: '>=0.0.0'
                );
            },
            array_unique($dependsList)
        );

        $dependsNormalized = array_reduce(
            $dependsNormalized,
            'array_replace',
            array()
        );

        return array_filter(
            array_replace(
                $dependsNormalized,
                array(PatchDefinition::BUNDLE_TARGET => false, '' => false)
            )
        );
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
