<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class DefinitionNormalizer
{
    public function process($patchTarget, $label, $data)
    {
        if (!is_array($data)) {
            $sourceSegments = explode('#', $data);
            $lastSegment = array_pop($sourceSegments);
            $hasSkip = ($lastSegment === PatchDefinition::SKIP);
            
            $data = array(
                PatchDefinition::SOURCE => $hasSkip 
                    ? implode('#', $sourceSegments) 
                    : (string)$data,
                PatchDefinition::SKIP => $hasSkip
            );
        }

        if (!isset($data[PatchDefinition::URL]) && !isset($data[PatchDefinition::SOURCE])) {
            return false;
        }

        return array(
            PatchDefinition::SKIP => isset($data[PatchDefinition::SKIP])
                ? $data[PatchDefinition::SKIP]
                : false,
            PatchDefinition::TARGETS => isset($data[PatchDefinition::TARGETS])
                ? $data[PatchDefinition::TARGETS]
                : array($patchTarget),
            PatchDefinition::SOURCE => isset($data[PatchDefinition::URL])
                ? $data[PatchDefinition::URL]
                : $data[PatchDefinition::SOURCE],
            PatchDefinition::LABEL => isset($data[PatchDefinition::LABEL])
                ? $data[PatchDefinition::LABEL]
                : $label,
            PatchDefinition::DEPENDS => isset($data[PatchDefinition::DEPENDS])
                ? $data[PatchDefinition::DEPENDS]
                : (isset($data[PatchDefinition::VERSION])
                    ? (is_array($data[PatchDefinition::VERSION])
                        ? $data[PatchDefinition::VERSION]
                        : array($patchTarget => $data[PatchDefinition::VERSION])
                    )
                    : array()
                )
        );
    }
}
