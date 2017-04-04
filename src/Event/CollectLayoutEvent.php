<?php

namespace MakinaCorpus\Drupal\Layout\Event;

use MakinaCorpus\Layout\Storage\LayoutInterface;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event raised in order to collect page layouts
 */
class CollectLayoutEvent extends Event
{
    /**
     * Event name
     */
    const EVENT_NAME = 'php_layout:collect';

    /**
     * @var bool[]
     */
    private $accessMap = [];

    /**
     * @var bool
     */
    private $displayForm = true;

    /**
     * @var LayoutInterface[]
     */
    private $layouts = [];

    /**
     * @var LayoutStorageInterface
     */
    private $storage;

    /**
     * Default constructor
     *
     * @param LayoutStorageInterface $storage
     */
    public function __construct(LayoutStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Get layout storage
     *
     * @return LayoutStorageInterface
     */
    public function getLayoutStorage() : LayoutStorageInterface
    {
        return $this->storage;
    }

    /**
     * Add layout to the current page
     *
     * @param LayoutInterface $layout
     * @param bool $isEditable
     */
    public function addLayout(LayoutInterface $layout, bool $isEditable = false)
    {
        $id = $layout->getId();
        $this->layouts[$id] = $layout;
        $this->accessMap[$id] = $isEditable;
    }

    /**
     * Let the core API display the form
     */
    public function showForm()
    {
        $this->displayForm = true;
    }

    /**
     * Do not let the core API display the form
     */
    public function hideForm()
    {
        $this->displayForm = false;
    }

    /**
     * Should be form managed by the core API
     *
     * @return bool
     */
    public function isFormEnabled() : bool
    {
        return $this->displayForm;
    }

    /**
     * Get all layouts
     *
     * @return LayoutInterface[]
     */
    public function getLayouts() : array
    {
        return $this->layouts;
    }

    /**
     * Get access map
     *
     * @return bool
     *   Keys are layout identifiers, values are booleans
     */
    public function getAccessMap() : array
    {
        return $this->accessMap;
    }
}
