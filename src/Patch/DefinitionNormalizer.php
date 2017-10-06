<?php
namespace Vaimo\ComposerPatches\Patch;

use Vaimo\ComposerPatches\Patch\Definition as PatchDefinition;

class DefinitionNormalizer
{
    public function process($patchTarget, $label, $data)
    {
        if (!is_array($data)) {
            $data = array(
                PatchDefinition::SOURCE => (string)$data
            );
        }

        if (!isset($data[PatchDefinition::URL]) && !isset($data[PatchDefinition::SOURCE])) {
            return false;
        }

        $source = isset($data[PatchDefinition::URL])
            ? $data[PatchDefinition::URL]
            : $data[PatchDefinition::SOURCE];

        $sourceSegments = explode('#', $source);
        $lastSegment = array_pop($sourceSegments);

        if ($lastSegment === PatchDefinition::SKIP) {
            $source = implode('#', $sourceSegments);
            $data[PatchDefinition::SKIP] = true;
        }
        
        return array(
            PatchDefinition::SOURCE => $source,
            PatchDefinition::TARGETS => isset($data[PatchDefinition::TARGETS])
                ? $data[PatchDefinition::TARGETS]
                : array($patchTarget),
            PatchDefinition::SKIP => isset($data[PatchDefinition::SKIP])
                ? $data[PatchDefinition::SKIP]
                : false,
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
