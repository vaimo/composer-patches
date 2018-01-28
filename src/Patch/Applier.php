<?php
namespace Vaimo\ComposerPatches\Patch;

class Applier
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;
    
    /**
     * @var \Vaimo\ComposerPatches\Shell
     */
    private $shell;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Vaimo\ComposerPatches\Utils\ApplierUtils
     */
    private $applierUtils;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     * @param array $config
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger,
        array $config
    ) {
        $this->logger = $logger;
        $this->config = $config;

        $this->shell = new \Vaimo\ComposerPatches\Shell($logger);
        $this->applierUtils = new \Vaimo\ComposerPatches\Utils\ApplierUtils();
    }

    public function applyFile($filename, $cwd, $config = array())
    {
        $result = false;

        list($type, $patchLevel, $operationName) = array_fill(0, 3, 'UNKNOWN');

        $applierConfig = $this->applierUtils->mergeConfig(
            $this->config, 
            array_filter($config)
        );

        $applierConfig = $this->applierUtils->sortConfig($applierConfig);
        
        $patchers = isset($applierConfig['patchers']) 
            ? array_filter($applierConfig['patchers']) 
            : array();
        
        $operations = isset($applierConfig['operations']) 
            ? array_filter($applierConfig['operations']) 
            : array();
        
        $levels = isset($applierConfig['levels']) 
            ? $applierConfig['levels'] 
            : array();

        if (!$patchers) {
            $this->logger->writeVerbose(
                sprintf(
                    'No valid patchers found with sequence: %s', 
                    implode(',', $applierConfig['sequence']['patchers'])
                ), 
                'error'
            );
        }
        
        foreach ($levels as $sequenceIndex => $patchLevel) {
            foreach ($patchers as $type => $patcher) {
                $result = true;
                
                foreach ($operations as $operationCode => $operationName) {
                    $args = array(
                        'level' => $patchLevel, 
                        'file' => $filename
                    );
                    
                    $result = $this->shell->execute($patcher[$operationCode], $args, $cwd) && $result;

                    if (!$result) {
                        break;
                    }
                }

                if ($result) {
                    break 2;
                }

                if ($sequenceIndex >= count($levels) - 1) {
                    continue;
                }

                $this->logger->writeVerbose(
                    '%s (type=%s) failed with p=%s. Retrying with p=%s',
                    'warning',
                    array($operationName, $type, $patchLevel, $levels[$sequenceIndex + 1])
                );
            }
        }

        if ($result) {
            $this->logger->writeVerbose('SUCCESS with type=%s (p=%s)', 'info', array($type, $patchLevel));
        } else {
            $this->logger->writeVerbose('FAILURE', 'error');
        }

        if (!$result) {
            throw new \Exception(sprintf('Cannot apply patch %s', $filename));
        }
    }
}
