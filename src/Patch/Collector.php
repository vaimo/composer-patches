<?php
namespace Vaimo\ComposerPatches\Patch;

class Collector
{
    /**
     * @var \Vaimo\ComposerPatches\Patch\DefinitionsProcessor
     */
    protected $definitionsProcessor;

    /**
     * @var \Vaimo\ComposerPatches\Json\Decoder
     */
    protected $jsonDecoder;

    public function __construct()
    {
        $this->definitionsProcessor = new \Vaimo\ComposerPatches\Patch\DefinitionsProcessor();
        $this->jsonDecoder = new \Vaimo\ComposerPatches\Json\Decoder();
    }

    public function gatherAllPatches($packages)
    {
        $allPatches = array();

        foreach ($packages as $patchOwnerPackage) {
            $extra = $patchOwnerPackage->getExtra();

            $patchDefinitionSources = array();

            if (isset($extra['patches'])) {
                $patchDefinitionSources[] = $extra['patches'];
            }

            if (isset($extra['patches-file'])) {
                $parsedPatchFileContents = $this->jsonDecoder->decode(
                    file_get_contents($extra['patches-file'])
                );

                if (isset($parsedPatchFileContents['patches'])) {
                    $patchDefinitionSources[] = $parsedPatchFileContents['patches'];
                } elseif (!$parsedPatchFileContents) {
                    throw new \Exception('There was an error in the supplied patch file');
                }
            }

            foreach ($patchDefinitionSources as $patches) {
                $patches = $this->definitionsProcessor->normalize($patches);

                foreach ($patches as $target => $definitions) {
                    if (!isset($allPatches[$target])) {
                        $allPatches[$target] = array();
                    }

                    foreach ($definitions as $definition) {
                        $allPatches[$target][] = array_replace($definition, array(
                            'owner' => $patchOwnerPackage->getName(),
                            'owner_type' => $patchOwnerPackage->getType()
                        ));
                    }
                }
            }
        }

        return $allPatches;
    }
}
