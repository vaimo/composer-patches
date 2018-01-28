<?php
namespace Vaimo\ComposerPatches;

class Shell
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    protected $logger;

    /**
     * @var \Composer\Util\ProcessExecutor
     */
    protected $processExecutor;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;
        
        $this->processExecutor =  new \Composer\Util\ProcessExecutor($logger->getOutputInstance());
    }

    public function execute($command, array $arguments, $cwd = null)
    {
        $logger = $this->logger;

        $outputHandler = function ($type, $data) use ($logger) {
            $logger->writeVerbose(trim($data), 'comment');
        };
        
        $arguments = array_combine(
            array_map(function ($item) {
                return sprintf('{{%s}}', $item);
            }, array_keys($arguments)),
            array_map('escapeshellarg', $arguments)
        );
        
        $result = $this->processExecutor->execute(
            str_replace(array_keys($arguments), $arguments, $command),
            $outputHandler,
            $cwd
        );

        return $result == 0;
    }
}
