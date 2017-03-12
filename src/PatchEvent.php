<?php
namespace Vaimo\ComposerPatches;

class PatchEvent extends \Composer\EventDispatcher\Event
{
    /**
     * @var \Composer\Package\PackageInterface $package
     */
    protected $package;

    /**
     * @var string $url
     */
    protected $url;

    /**
     * @var string $description
     */
    protected $description;

    public function __construct($eventName, \Composer\Package\PackageInterface $package, $url, $description)
    {
        parent::__construct($eventName);

        $this->package = $package;
        $this->url = $url;
        $this->description = $description;
    }

    public function getPackage()
    {
        return $this->package;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
