<?php

namespace MakinaCorpus\Drupal\Layout\Type;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Layout\Error\TypeMismatchError;
use MakinaCorpus\Layout\Grid\Item;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Render\RenderCollection;
use MakinaCorpus\Layout\Type\ItemTypeInterface;

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
    public function create(string $id, string $style = null) : ItemInterface
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
    public function getAllowedStylesFor(ItemInterface $item) : array
    {
        // We need to list the available view modes for a node
        // @todo this code is NOT d8 friendly
        $node = $this->entityManager->getStorage('node')->load($item->getId());

        if (!$node) {
            return [ItemInterface::STYLE_DEFAULT => t("Default")];
        }

        // $settings = field_view_mode_settings('node', $node->bundle());
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function renderItem(ItemInterface $item, RenderCollection $collection)
    {
        $storage = $this->entityManager->getStorage('node');

        if ($node = $storage->load($item->getId())) {
            $output = node_view($node, $this->getViewModeFromItem($item));

            $collection->setOutputFor($item, drupal_render($output));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderAllItems(array $items, RenderCollection $collection)
    {
        if (!$items) {
            return;
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
                $output = drupal_render($rendered[$id]);

                $collection->setOutputWith('node', $id, $viewMode, $output);
                if ('teaser' === $viewMode) {
                    $collection->setOutputWith('node', $id, ItemInterface::STYLE_DEFAULT, $output);
                }
            }
        }
    }
}
