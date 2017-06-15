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

        if (!isset($data['url']) && !isset($data[PatchDefinition::SOURCE])) {
            return false;
        }

        return array(
            PatchDefinition::SOURCE => isset($data['url'])
                ? $data['url']
                : $data[PatchDefinition::SOURCE],
            PatchDefinition::LABEL => isset($data[PatchDefinition::LABEL])
                ? $data[PatchDefinition::LABEL]
                : $label,
            PatchDefinition::VERSION => isset($data[PatchDefinition::VERSION])
                ? (is_array($data[PatchDefinition::VERSION])
                    ? $data[PatchDefinition::VERSION]
                    : array($patchTarget => $data[PatchDefinition::VERSION]))
                : array()
        );
    }
}
