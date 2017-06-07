<?php
namespace Vaimo\ComposerPatches;

class Shell
{
    /**
     * @var \Composer\Util\ProcessExecutor
     */
    protected $executor;

    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    protected $logger;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->executor =  new \Composer\Util\ProcessExecutor($logger->getOutputInstance());
        $this->logger = $logger;
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
