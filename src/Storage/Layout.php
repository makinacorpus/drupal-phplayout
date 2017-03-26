<?php

namespace MakinaCorpus\Drupal\Layout\Storage;

use MakinaCorpus\Layout\Grid\VerticalContainer;
use MakinaCorpus\Layout\Storage\AbstractLayout;

/**
 * Default layout instances
 */
class Layout extends AbstractLayout
{
    private $id;
    private $nodeId;
    private $siteId;
    private $region;
    private $container;

    /**
     * Get layout identifier
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Get node identifier this layout is attached to if any
     *
     * @return null|int
     */
    public function getNodeId()
    {
        return $this->nodeId;
    }

    /**
     * Get site identifier this layout is attached to if any
     *
     * @return null|int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * Get region this layout is attached to if any
     *
     * @return null|string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Get top level container
     *
     * @return VerticalContainer
     */
    public function getTopLevelContainer() : VerticalContainer
    {
        if (!$this->container) {
            $this->container = new VerticalContainer('layout-' . $this->id);
            $this->container->setStorageId($this->id, 0, false);
        }

        return $this->container;
    }
}
