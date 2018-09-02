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

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;
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
            '--from-source',
            null,
            InputOption::VALUE_NONE,
            'Apply patches based on information directly from packages in vendor folder'
        );

        $this->addOption(
            '--graceful',
            null,
            InputOption::VALUE_NONE,
            'Continue even when some patch fails to apply'
        );

        $this->addOption(
            '--force',
            null,
            InputOption::VALUE_NONE,
            'Force package reset even when it has local change'
        );
    }

    protected function getBehaviourFlags(InputInterface $input)
    {
        return array(
            'redo' => (bool)$input->getOption('redo'),
            'undo' => (bool)$input->getOption('undo')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configDefaults = new \Vaimo\ComposerPatches\Config\Defaults();
        $defaults = $configDefaults->getPatcherConfig();

        $composer = $this->getComposer();

        $behaviourFlags = $this->getBehaviourFlags($input);
        $shouldUndo = !$behaviourFlags['redo'] && $behaviourFlags['undo'];

        $filters = array(
            PatchDefinition::SOURCE => $input->getOption('filter'),
            PatchDefinition::TARGETS => $input->getArgument('targets')
        );

        $configFactory = new \Vaimo\ComposerPatches\Factories\ConfigFactory($composer, array(
            Config::PATCHER_FORCE_REAPPLY => $behaviourFlags['redo'] || $behaviourFlags['undo'],
            Config::PATCHER_FROM_SOURCE => (bool)$input->getOption('from-source'),
            Config::PATCHER_GRACEFUL => (bool)$input->getOption('graceful'),
            Config::PATCHER_SOURCES => array_fill_keys(array_keys($defaults[Config::PATCHER_SOURCES]), true)
        ));

        $bootstrap = new \Vaimo\ComposerPatches\Bootstrap($composer, $this->getIO(), $configFactory);

        $isDevMode = !$input->getOption('no-dev');

        $filterUtils = new \Vaimo\ComposerPatches\Utils\FilterUtils();

        putenv(Environment::FORCE_RESET . "=" . (bool)$input->getOption('force'));

        if ($shouldUndo && !array_filter($filters)) {
            $bootstrap->stripPatches($isDevMode);
        } else {
            if ($shouldUndo) {
                $filters[PatchDefinition::SOURCE] = $filterUtils->invertRules(
                    $filters[PatchDefinition::SOURCE] ? $filters[PatchDefinition::SOURCE] : array('*')
                );
            }

            $bootstrap->applyPatches(
                $isDevMode, 
                array_filter($filters),
                $behaviourFlags['redo'] ? array('related') : array('direct', 'related') 
            );
            
            $bootstrap->sanitizeLocker($composer->getLocker());
        }

        $composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_INSTALL_CMD, $isDevMode);
    }
}
