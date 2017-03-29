<?php

namespace MakinaCorpus\Drupal\Layout\Storage;

use MakinaCorpus\Layout\Grid\TopLevelContainer;
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getTopLevelContainer() : TopLevelContainer
    {
        if (!$this->container) {
            $this->container = new TopLevelContainer('layout-' . $this->id);
            $this->container->setStorageId($this->id, 0, false);
        }

        return $this->container;
    }
}
