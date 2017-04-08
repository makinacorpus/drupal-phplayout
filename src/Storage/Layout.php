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
     * Create top level container
     *
     * @return TopLevelContainer
     */
    private function createTopLevelContainer() : TopLevelContainer
    {
        $instance = new TopLevelContainer($this->id);
        $instance->setStorageId($this->id, $this->id, false);

        $region   = $this->region ?: 'default';
        $options  = variable_get('phplayout_region_options', []);

        if (isset($options[$region])) {
            $instance->setOptions($options[$region], true);
        } else if (isset($options['default'])) {
            $instance->setOptions($options['default'], true);
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopLevelContainer() : TopLevelContainer
    {
        if (!$this->container) {
            $this->container = $this->createTopLevelContainer();
        }

        return $this->container;
    }
}
