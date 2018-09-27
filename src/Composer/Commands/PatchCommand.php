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
use Vaimo\ComposerPatches\Repository\PatchesApplier\ListResolvers;

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

    protected function getBehaviourFlags(InputInterface $input)
    {
        return array(
            'redo' => (bool)$input->getOption('redo'),
            'undo' => (bool)$input->getOption('undo')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $io = $this->getIO();
        
        $bootstrapFactory = new \Vaimo\ComposerPatches\Factories\BootstrapFactory($composer, $io);

        $isExplicit = $input->getOption('explicit');
        $isDevMode = !$input->getOption('no-dev');

        $filters = array(
            PatchDefinition::SOURCE => $input->getOption('filter'),
            PatchDefinition::TARGETS => $input->getArgument('targets')
        );

        $behaviourFlags = $this->getBehaviourFlags($input);
        $shouldUndo = !$behaviourFlags['redo'] && $behaviourFlags['undo'];

        $listResolver = new ListResolvers\FilteredListResolver($filters);
        
        if ($shouldUndo) {
            $listResolver = new ListResolvers\InvertedListResolver($listResolver);
        } else {
            $listResolver = new ListResolvers\InclusiveListResolver($listResolver);
        }

        $config = array(
            \Vaimo\ComposerPatches\Config::PATCHER_SOURCES => array(
                'project' => true,
                'packages' => true,
                'vendors' => true
            )
        );
        
        $bootstrap = $bootstrapFactory->create($listResolver, $config, $isExplicit);

        putenv(Environment::FORCE_RESET . '=' . (bool)$input->getOption('force'));

        if ($shouldUndo && !array_filter($filters)) {
            $bootstrap->stripPatches($isDevMode);
        } else {
            putenv(Environment::PREFER_OWNER . '=' . $input->getOption('from-source'));
            putenv(Environment::FORCE_REAPPLY . '=' . $behaviourFlags['redo']);
            
            $bootstrap->applyPatches($isDevMode);
            $bootstrap->sanitizeLocker();
        }

        $composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_INSTALL_CMD, $isDevMode);
    }
}
