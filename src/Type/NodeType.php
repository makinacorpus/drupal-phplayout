<?php

namespace MakinaCorpus\Drupal\Layout\Type;

use MakinaCorpus\Layout\Type\ItemTypeInterface;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Render\RenderCollection;

/**
 * Supports node as layout items
 */
class NodeType implements ItemTypeInterface
{
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
        throw new \Exception("not implemented yet");
    }

    /**
     * Preload items data if necessary, this will be call at runtime prior
     * to full grid rendering, it allows the implementor to have access to
     * a flatten tree and preload everything it can
     *
     * @param ItemInterface[] $items
     *
     * @throws TypeMismatchError
     *   In case one of the items has not the right type
     */
    public function preload(array $items)
    {
        throw new \Exception("not implemented yet");
    }

    /**
     * Render a single item
     *
     * @param ItemInterface $item
     *   Item to render
     * @param RenderCollection $collection
     *   Already rendered items
     *
     * @return string
     *   If the item is not renderable or invalid, return an empty string
     *
     * @throws TypeMismatchError
     *   In case the item has not the right type
     */
    public function renderItem(ItemInterface $item, RenderCollection $collection) : string
    {
        throw new \Exception("not implemented yet");
    }

    /**
     * Render an array of items
     *
     * @param ItemInterface[] $items
     *   Items to render
     * @param RenderCollection $collection
     *   Already rendered items
     *
     * @return string[]
     *   Array of render strings, keyed using item identifiers, invalid items
     *   must be silently removed from the returned array, no errors
     *
     * @throws TypeMismatchError
     *   In case one of the items has not the right type
     */
    public function renderAllItems(array $items, RenderCollection $collection) : array
    {
        throw new \Exception("not implemented yet");
    }
}
