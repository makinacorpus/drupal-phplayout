<?php

namespace MakinaCorpus\Drupal\Layout\Type;

use MakinaCorpus\Layout\Error\TypeMismatchError;
use MakinaCorpus\Layout\Grid\Item;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Render\RenderCollection;
use MakinaCorpus\Layout\Type\ItemTypeInterface;

/**
 * Special item for displaying the page content as an item type
 */
class PageContentType implements ItemTypeInterface
{
    /**
     * This makes it stateless, and I don't like that, but I need it.
     *
     * @var string
     */
    private $pageContent;

    /**
     * @var bool
     */
    private $isRendered = false;

    /**
     * {@inheritdoc}
     */
    public function getType() : string
    {
        return 'page';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(string $id) : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $id, string $style = null) : ItemInterface
    {
        return new Item('page', $id, $style);
    }

    /**
     * {@inheritdoc}
     */
    public function preload(array $items)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedStylesFor(ItemInterface $item) : array
    {
        return [ItemInterface::STYLE_DEFAULT  => t("Default")];
    }

    /**
     * {@inheritdoc}
     */
    public function renderItem(ItemInterface $item, RenderCollection $collection)
    {
        $this->renderAllItems([$item], $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAllItems(array $items, RenderCollection $collection)
    {
        foreach ($items as $item) {

            if ('page' !== $item->getType()) {
                throw new TypeMismatchError();
            }

            if (!$this->isRendered) {
                $output = drupal_set_page_content();
                $this->pageContent = drupal_render($output);
            }

            $collection->setOutputFor($item, $this->pageContent);
        }
    }
}
