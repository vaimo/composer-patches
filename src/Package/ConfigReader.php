<?php
namespace Vaimo\ComposerPatches\Package;

class ConfigReader
{
    /**
     * @var \Vaimo\ComposerPatches\Json\Decoder
     */
    private $jsonDecoder;

    public function __construct()
    {
        $this->jsonDecoder = new \Vaimo\ComposerPatches\Json\Decoder();
    }
    
    public function readToArray($source)
    {
        $sourceData = file_get_contents($source);

        try {
            $fileContents = $this->jsonDecoder->decode($sourceData);
        } catch (\Vaimo\ComposerPatches\Exceptions\DecoderException $exception) {
            $message = sprintf('Failed to retrieve contents of %s', $source);

            throw new \Vaimo\ComposerPatches\Exceptions\ReadException($message, 0, $exception);
        }
        
        return $fileContents;
    }
}
