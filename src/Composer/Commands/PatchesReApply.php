<?php
namespace Vaimo\ComposerPatches\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Vaimo\ComposerPatches\Environment;

class PatchesReApply extends \Composer\Command\BaseCommand
{
    protected function configure()
    {
        $this->setName('patch');
        $this->setDescription('Apply registered patches to current project');

        $this->addArgument(
            'targets', \Symfony\Component\Console\Input\InputArgument::IS_ARRAY, 'Packages for the patcher to target', array()
        );
        
        $this->addOption(
            '--no-dev', null, InputOption::VALUE_NONE, 'Disables installation of require-dev packages'
        );

        $this->addOption(
            '--redo', null, InputOption::VALUE_NONE, 'Re-patch every package that has patches defined against it'
        );

        $this->addOption(
            '--reset', null, InputOption::VALUE_NONE, 'Remove all patches.'
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
        
        if ($input->getOption('reset')) {
            $bootstrap->unload($targets);
            
            return;
        }
        
        $isDevMode = !$input->getOption('no-dev');

        putenv(Environment::PREFER_OWNER . "=" . $input->getOption('from-source'));
        putenv(Environment::FORCE_REAPPLY . "=" . $input->getOption('redo'));

        $bootstrap->apply($isDevMode, $targets);
    }
}
