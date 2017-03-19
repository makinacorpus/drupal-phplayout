<?php

namespace MakinaCorpus\Drupal\Layout\Type;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Layout\Error\TypeMismatchError;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Render\RenderCollection;
use MakinaCorpus\Layout\Type\ItemTypeInterface;
use MakinaCorpus\Layout\Grid\Item;

/**
 * Supports node as layout items
 */
class NodeType implements ItemTypeInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getType() : string
    {
        return 'node';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(string $id) : bool
    {
        throw new \Exception("not implemented yet");
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $id, string $style = null, array $options = []) : ItemInterface
    {
        return new Item('node', $id, $style);
    }

    /**
     * Get node identifier list from items
     *
     * @param ItemInterface[] $items
     *
     * @throws TypeMismatchError
     *
     * @return int[]
     */
    private function getNodeIdListFromItems(array $items) : array
    {
        return array_map(
            function (ItemInterface $item) {
                $id = $item->getId();
                if (!is_numeric($id)) {
                    throw new TypeMismatchError();
                }
                return $id;
            },
            $items
        );
    }

    /**
     * Get view mode from item
     *
     * @param ItemInterface $item
     *
     * @return string
     */
    private function getViewModeFromItem(ItemInterface $item) : string
    {
        $viewMode = $item->getStyle();

        if (ItemInterface::STYLE_DEFAULT === $viewMode) {
            $viewMode = 'teaser';
        }

        return $viewMode;
    }

    /**
     * {@inheritdoc}
     */
    public function preload(array $items)
    {
        if (!$items) {
            return;
        }

        $this->entityManager->getStorage('node')->loadMultiple($this->getNodeIdListFromItems($items));
    }

    /**
     * {@inheritdoc}
     */
    public function renderItem(ItemInterface $item, RenderCollection $collection) : string
    {
        $storage = $this->entityManager->getStorage('node');

        if ($node = $storage->load($item->getId())) {
            $output = node_view($node, $this->getViewModeFromItem($item));

            return drupal_render($output);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAllItems(array $items, RenderCollection $collection) : array
    {
        $ret = [];

        if (!$items) {
            return $ret;
        }

        // Preload all nodes, we are going to need it
        $preloaded = $this->entityManager->getStorage('node')->loadMultiple($this->getNodeIdListFromItems($items));

        // First fetch all items using view modes
        $sorted = [];
        foreach ($items as $item) {
            $id = $item->getId();
            if (isset($preloaded[$id])) {
                $sorted[$this->getViewModeFromItem($item)][$id] = $preloaded[$id];
            }
        }

        // Bulk render using view modes
        foreach ($sorted as $viewMode => $nodes) {
            $rendered = node_view_multiple($nodes, $viewMode)['nodes'];
            // element_children() allows to drop #ish keys from the render array
            foreach (element_children($rendered) as $id) {
                $ret[$id] = drupal_render($rendered[$id]);
            }
        }

        // @todo we should add missing nodes as empty strings

        return $ret;
    }
}
