<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Composer\Script\ScriptEvents;

use Vaimo\ComposerPatches\Patch\Definition as Patch;
use Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;
use Vaimo\ComposerPatches\Config;
use Vaimo\ComposerPatches\Interfaces\ListResolverInterface;
use Vaimo\ComposerPatches\Composer\Plugin\Behaviour;
use Vaimo\ComposerPatches\Environment;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PatchCommand extends \Composer\Command\BaseCommand
{
    protected function configure()
    {
        $this->setName('patch');
        $this->setDescription('Apply registered patches to current project');

        $this->addArgument(
            'targets',
            \Symfony\Component\Console\Input\InputArgument::IS_ARRAY,
            'Packages for the patcher to target',
            array()
        );

        $this->addOption(
            '--redo',
            null,
            InputOption::VALUE_NONE,
            'Re-patch all packages or a specific package when targets defined'
        );

        $this->addOption(
            '--undo',
            null,
            InputOption::VALUE_NONE,
            'Remove all patches or a specific patch when targets defined'
        );

        $this->addOption(
            '--no-dev',
            null,
            InputOption::VALUE_NONE,
            'Disables installation of require-dev packages'
        );

        $this->addOption(
            '--filter',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Apply only those patch files/sources that match with provided filter'
        );

        $this->addOption(
            '--explicit',
            null,
            InputOption::VALUE_NONE,
            'Show information for every patch that gets re-applied (due to package reset)'
        );

        $this->addOption(
            '--show-reapplies',
            null,
            InputOption::VALUE_NONE,
            'Alias for \'explicit\' argument'
        );

        $this->addOption(
            '--from-source',
            null,
            InputOption::VALUE_NONE,
            'Apply patches based on information directly from packages in vendor folder'
        );

        $this->addOption(
            '--force',
            null,
            InputOption::VALUE_NONE,
            'Force package reset even when it has local change'
        );

        $this->addOption(
            '--no-scripts',
            null,
            InputOption::VALUE_NONE,
            'Skips the execution of all scripts defined in composer.json file.'
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $appIO = $this->getIO();

        $isDevMode = !$input->getOption('no-dev');

        $behaviourFlags = $this->getBehaviourFlags($input);
        $shouldUndo = !$behaviourFlags[Behaviour::REDO] && $behaviourFlags[Behaviour::UNDO];

        $filters = $this->resolveActiveFilters($input, $behaviourFlags);

        $listResolver = $this->createListResolver($behaviourFlags, $filters);

        $runtimeUtils = new \Vaimo\ComposerPatches\Utils\RuntimeUtils();

        $config = array(
            Config::PATCHER_SOURCES => $this->createSourcesEnablerConfig()
        );

        $this->configureEnvironmentForBehaviour($behaviourFlags);

        $outputTriggers = $this->resolveOutputTriggers($filters, $behaviourFlags);

        $contextFactory = new \Vaimo\ComposerPatches\Factories\ComposerContextFactory($composer);
        $composerContext = $contextFactory->create();

        $lockSanitizer = new \Vaimo\ComposerPatches\Repository\Lock\Sanitizer($appIO);
        $bootstrapFactory = new \Vaimo\ComposerPatches\Factories\BootstrapFactory($composerContext, $appIO);
        $outputStrategy = new \Vaimo\ComposerPatches\Strategies\OutputStrategy($outputTriggers);

        $bootstrap = $bootstrapFactory->create($listResolver, $outputStrategy, $config);

        $result = $runtimeUtils->executeWithPostAction(
            function () use ($shouldUndo, $filters, $isDevMode, $bootstrap, $runtimeUtils, $input, $behaviourFlags) {
                if ($shouldUndo && !array_filter($filters)) {
                    $bootstrap->stripPatches($isDevMode);

                    return true;
                }

                $runtimeUtils->setEnvironmentValues(array(
                    Environment::PREFER_OWNER => $input->getOption('from-source'),
                    Environment::FORCE_REAPPLY => $behaviourFlags[Behaviour::REDO]
                ));

                return $bootstrap->applyPatches($isDevMode);
            },
            function () use ($composer, $lockSanitizer, $isDevMode) {
                $repository = $composer->getRepositoryManager()->getLocalRepository();

                $repository->write($isDevMode, $composer->getInstallationManager());
                $lockSanitizer->sanitize();
            }
        );

        if (!$input->getOption('no-scripts')) {
            $composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_INSTALL_CMD, $isDevMode);
        }

        return (int)!$result;
    }

    private function createSourcesEnablerConfig()
    {
        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();

        $defaultValues = $configDefaults->getPatcherConfig();

        if (isset($defaultValues[Config::PATCHER_SOURCES]) && is_array($defaultValues[Config::PATCHER_SOURCES])) {
            $sourceTypes = array_keys((array)$defaultValues[Config::PATCHER_SOURCES]);

            return array_fill_keys($sourceTypes, true);
        }

        return array();
    }

    protected function getBehaviourFlags(InputInterface $input)
    {
        return array(
            Behaviour::REDO => $this->getOptionGraceful($input, 'redo'),
            Behaviour::UNDO => $this->getOptionGraceful($input, 'undo'),
            Behaviour::FORCE => $this->getOptionGraceful($input, 'force'),
            Behaviour::EXPLICIT => $this->getOptionGraceful($input, 'explicit')
                || $this->getOptionGraceful($input, 'show-reapplies')
        );
    }

    private function getOptionGraceful(InputInterface $input, $name)
    {
        return $input->hasOption($name) && $input->getOption($name);
    }

    private function resolveActiveFilters(InputInterface $input, array $behaviourFlags)
    {
        $filters = array(
            Patch::SOURCE => $input->getOption('filter'),
            Patch::TARGETS => $input->getArgument('targets')
        );

        $hasFilers = (bool)array_filter($filters);

        if (!$hasFilers && $behaviourFlags[Behaviour::REDO]) {
            $filters[Patch::SOURCE] = array('*');
        }

        return $filters;
    }

    private function configureEnvironmentForBehaviour(array $behaviourFlags)
    {
        $runtimeUtils = new \Vaimo\ComposerPatches\Utils\RuntimeUtils();

        if ($behaviourFlags[Behaviour::REDO] || $behaviourFlags[Behaviour::UNDO]) {
            $runtimeUtils->setEnvironmentValues(array(
                Environment::EXIT_ON_FAIL => 0,
                'COMPOSER_EXIT_ON_PATCH_FAILURE' => 0
            ));
        }

        $runtimeUtils->setEnvironmentValues(array(
            Environment::FORCE_RESET => (int)$behaviourFlags[Behaviour::FORCE]
        ));
    }

    private function resolveOutputTriggers(array $filters, array $behaviourFlags)
    {
        $hasFilers = (bool)array_filter($filters);

        $isExplicit = $behaviourFlags[Behaviour::EXPLICIT];

        if (!$hasFilers && $behaviourFlags[Behaviour::REDO]) {
            $isExplicit = true;
        }

        $outputTriggerFlags = array(
            Patch::STATUS_NEW => !$hasFilers,
            Patch::STATUS_CHANGED => !$hasFilers,
            Patch::STATUS_MATCH => true,
            Patch::SOURCE => $isExplicit,
            Patch::URL => $isExplicit
        );

        return array_keys(
            array_filter($outputTriggerFlags)
        );
    }

    private function createListResolver(array $behaviourFlags, array $filters)
    {
        $listResolver = new ListResolvers\FilteredListResolver($filters);

        $isDefaultBehaviour = !$behaviourFlags[Behaviour::REDO] && !$behaviourFlags[Behaviour::UNDO];

        $listResolver = $this->attachBehaviourToListResolver($listResolver, $behaviourFlags);

        if ($isDefaultBehaviour) {
            $listResolver = new ListResolvers\ChangesListResolver($listResolver);
        }

        return $listResolver;
    }

    private function attachBehaviourToListResolver(ListResolverInterface $listResolver, array $behaviourFlags)
    {
        $shouldUndo = !$behaviourFlags[Behaviour::REDO] && $behaviourFlags[Behaviour::UNDO];

        if ($shouldUndo) {
            return new ListResolvers\InvertedListResolver($listResolver);
        }

        return new ListResolvers\InclusiveListResolver($listResolver);
    }
}
