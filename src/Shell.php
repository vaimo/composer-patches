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
    protected $executor;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;

        $this->executor =  new \Composer\Util\ProcessExecutor($logger->getOutputInstance());
    }

    public function execute($commandTemplate, array $arguments, $cwd = null)
    {
        $logger = $this->logger;

        $outputHandler = function ($type, $data) use ($logger) {
            $logger->writeVerbose(trim($data), 'comment');
        };

        $result = $this->executor->execute(
            vsprintf($commandTemplate, array_map('escapeshellarg', $arguments)),
            $outputHandler,
            $cwd
        );

        return $result == 0;
    }
}
