<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Vaimo\ComposerPatches\Environment;

class PatchCommand extends \Composer\Command\BaseCommand
{
    protected function configure()
    {
        $this->setName('patch');
        $this->setDescription('Apply registered patches to current project');

        $this->addArgument(
            'targets', \Symfony\Component\Console\Input\InputArgument::IS_ARRAY, 'Packages for the patcher to target', array()
        );

        $this->addOption(
            '--redo', null, InputOption::VALUE_NONE, 'Re-patch all packages or a specific package when targets defined'
        );

        $this->addOption(
            '--undo', null, InputOption::VALUE_NONE, 'Remove all patches or a specific patch when targets defined'
        );
        
        $this->addOption(
            '--no-dev', null, InputOption::VALUE_NONE, 'Disables installation of require-dev packages'
        );

        $this->addOption(
            '--filter', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Apply only those patch files/sources that match with provided filter'
        );
        
        $this->addOption(
            '--from-source', null, InputOption::VALUE_NONE, 'Apply patches based on information directly from packages in vendor folder'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bootstrap = new \Vaimo\ComposerPatches\Bootstrap(
            $this->getComposer(),
            $this->getIO()
        );

        $targets = $input->getArgument('targets');
        $filters = $input->getOption('filter');

        putenv(Environment::FORCE_REAPPLY . "=" . ($input->getOption('redo') || $input->getOption('undo')));

        if ($input->getOption('undo')) {
            $bootstrap->unload($targets);
            return;
        }
        
        $isDevMode = !$input->getOption('no-dev');

        putenv(Environment::PREFER_OWNER . "=" . $input->getOption('from-source'));

        $bootstrap->apply($isDevMode, $targets, $filters);
    }
}
