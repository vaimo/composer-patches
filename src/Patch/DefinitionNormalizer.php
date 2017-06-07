<?php
namespace Vaimo\ComposerPatches\Patch;

class DefinitionNormalizer
{
    public function process($label, $data)
    {
        if (!is_array($data)) {
            $data = array(
                'source' => (string)$data
            );
        }

        if (!isset($data['url']) && !isset($data['source'])) {
            return false;
        }

        return array(
            'source' => isset($data['url']) ? $data['url'] : $data['source'],
            'label' => isset($data['label']) ? $data['label'] : $label,
            'version' => isset($data['version']) ? $data['version'] : false
        );
    }
}
