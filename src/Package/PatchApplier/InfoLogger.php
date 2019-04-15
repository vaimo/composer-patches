<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Package\PatchApplier;

use Vaimo\ComposerPatches\Patch\Definition as Patch;

class InfoLogger
{
    /**
     * @var \Vaimo\ComposerPatches\Logger
     */
    private $logger;
    
    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;
    }
    
    public function outputPatchSource(array $patch)
    {
        $label = '';
        
        if (isset($patch[Patch::STATUS_LABEL]) && $patch[Patch::STATUS_LABEL]) {
            $label = $patch[Patch::STATUS_LABEL];
        }

        $label = $label ? sprintf(' [<info>%s</info>]', $label) : '';
        
        $messageTemplate = '%s%s';
        $args = array($patch[Patch::SOURCE], $label);
        
        if ($patch[Patch::OWNER] && $patch[Patch::OWNER] !== Patch::OWNER_UNKNOWN) {
            $messageTemplate = '<info>%s</info>: ' . $messageTemplate;
            array_unshift($args, $patch[Patch::OWNER]);
        }

        $this->logger->writeRaw($messageTemplate, $args);
    }

    public function outputPatchDescription(array $patch)
    {
        if (!trim($patch[Patch::LABEL])) {
            return;
        }

        $labelLines = $this->resolveLabelLines($patch);

        foreach ($labelLines as $line) {
            $this->logger->write('comment', $line);
        }
    }
    
    public function outputPatchInfo(array $patch)
    {
        $this->outputPatchSource($patch);

        $loggerIndentation = $this->logger->push();

        $this->outputPatchDescription($patch);
        
        $this->logger->reset($loggerIndentation);
    }
    
    private function resolveLabelLines(array $patch)
    {
        $lines = explode(PHP_EOL, $patch[Patch::LABEL]);

        $reference = $this->resolveReferenceInfo($patch);

        if (!$reference) {
            return $lines;
        }

        if (count($lines) > 1) {
            return array_merge($lines, array(
                sprintf('reference: <fg=default;options=underscore>%s</>', $reference)
            ));
        }

        return array(sprintf('%s (<fg=default;options=underscore>%s</>)', reset($lines), $reference));
    }
    
    private function resolveReferenceInfo(array $patch)
    {
        if (isset($patch[Patch::LINK]) && $patch[Patch::LINK]) {
            return $patch[Patch::LINK];
        }
        
        if (isset($patch[Patch::ISSUE]) && $patch[Patch::ISSUE]) {
            return $patch[Patch::ISSUE];
        }
        
        return false;
    }
}
