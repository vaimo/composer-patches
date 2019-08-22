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

use Vaimo\ComposerPatches\Environment;

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
        $shouldUndo = !$behaviourFlags['redo'] && $behaviourFlags['undo'];

        $filters = $this->resolveActiveFilters($input, $behaviourFlags);
        
        $listResolver = $this->createListResolver($behaviourFlags, $filters);
        
        $runtimeUtils = new \Vaimo\ComposerPatches\Utils\RuntimeUtils();
        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();
        
        $defaultValues = $configDefaults->getPatcherConfig();
        
        $config = array(
            Config::PATCHER_SOURCES => array_fill_keys(
                array_keys($defaultValues[Config::PATCHER_SOURCES]),
                true
            )
        );

        $this->configureEnvironmentForBehaviour($behaviourFlags);
        
        $outputTriggers = $this->resolveOutputTriggers($filters, $behaviourFlags);

        $lockSanitizer = new \Vaimo\ComposerPatches\Repository\Lock\Sanitizer($appIO);
        $bootstrapFactory = new \Vaimo\ComposerPatches\Factories\BootstrapFactory($composer, $appIO);
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
                    Environment::FORCE_REAPPLY => $behaviourFlags['redo']
                ));

                return $bootstrap->applyPatches($isDevMode);
            },
            function () use ($composer, $lockSanitizer) {
                $repository = $composer->getRepositoryManager()->getLocalRepository();

                $repository->write();
                $lockSanitizer->sanitize();
            }
        );

        $composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_INSTALL_CMD, $isDevMode);

        return (int)!$result;
    }

    protected function getBehaviourFlags(InputInterface $input)
    {
        return array(
            'redo' => $this->getOptionGraceful($input, 'redo'),
            'undo' => $this->getOptionGraceful($input, 'undo'),
            'force' => $this->getOptionGraceful($input, 'force'),
            'explicit' => $this->getOptionGraceful($input, 'explicit') 
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

        if (!$hasFilers && $behaviourFlags['redo']) {
            $filters[Patch::SOURCE] = array('*');
        }

        return $filters;
    }
    
    private function configureEnvironmentForBehaviour(array $behaviourFlags)
    {
        $runtimeUtils = new \Vaimo\ComposerPatches\Utils\RuntimeUtils();

        if ($behaviourFlags['redo'] || $behaviourFlags['undo']) {
            $runtimeUtils->setEnvironmentValues(array(
                Environment::EXIT_ON_FAIL => 0,
                'COMPOSER_EXIT_ON_PATCH_FAILURE' => 0
            ));
        }

        $runtimeUtils->setEnvironmentValues(array(
            Environment::FORCE_RESET => (int)$behaviourFlags['force']
        ));
    }
    
    private function resolveOutputTriggers(array $filters, array $behaviourFlags)
    {
        $hasFilers = (bool)array_filter($filters);

        $isExplicit = $behaviourFlags['explicit'];
        
        if (!$hasFilers && $behaviourFlags['redo']) {
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

        $shouldUndo = !$behaviourFlags['redo'] && $behaviourFlags['undo'];
        $isDefaultBehaviour = !$behaviourFlags['redo'] && !$behaviourFlags['undo'];
        
        if ($shouldUndo) {
            $listResolver = new ListResolvers\InvertedListResolver($listResolver);
        } else {
            $listResolver = new ListResolvers\InclusiveListResolver($listResolver);
        }

        if ($isDefaultBehaviour) {
            $listResolver = new ListResolvers\ChangesListResolver($listResolver);
        }
        
        return $listResolver;
    }
}
