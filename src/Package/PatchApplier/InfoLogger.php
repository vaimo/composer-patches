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
     * @var array
     */
    private $stateLabels;

    /**
     * @param \Vaimo\ComposerPatches\Logger $logger
     */
    public function __construct(
        \Vaimo\ComposerPatches\Logger $logger
    ) {
        $this->logger = $logger;
        
        $this->stateLabels = array(
            Patch::STATUS_NEW => 'NEW',
            Patch::STATUS_CHANGED => 'CHANGED'
        );
    }
    
    public function outputPatchSource(array $patch)
    {
        if (isset($patch[Patch::STATUS_LABEL]) && $patch[Patch::STATUS_LABEL]) {
            $labelMatches = array($patch[Patch::STATUS_LABEL]);
        } else {
            $labelMatches = array_intersect_key($this->stateLabels, array_filter($patch));
        }

        $label = $labelMatches ? vsprintf(' [<info>%s</info>]', $labelMatches) : '';

        $isOwnerUnknown = !$patch[Patch::OWNER] || $patch[Patch::OWNER] === Patch::OWNER_UNKNOWN;

        if ($isOwnerUnknown) {
            $this->logger->writeRaw('%s%s', array($patch[Patch::SOURCE], $label));
        } else {
            $this->logger->writeRaw(
                '<info>%s</info>: %s%s',
                array($patch[Patch::OWNER], $patch[Patch::SOURCE], $label)
            );
        }
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
