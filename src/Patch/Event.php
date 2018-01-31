<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches\Patch;

class Event extends \Composer\EventDispatcher\Event
{
    /**
     * @var \Composer\Package\PackageInterface $package
     */
    private $package;

    /**
     * @var string $url
     */
    private $url;

    /**
     * @var string $description
     */
    private $description;

    /**
     * @param string $eventName
     * @param \Composer\Package\PackageInterface $package
     * @param string $url
     * @param string $description
     */
    public function __construct(
        $eventName,
        \Composer\Package\PackageInterface $package,
        $url,
        $description
    ) {
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
