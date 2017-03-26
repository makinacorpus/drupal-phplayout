<?php

namespace MakinaCorpus\Drupal\Layout\Render;

use MakinaCorpus\Layout\Controller\Context;
use MakinaCorpus\Layout\Grid\ColumnContainer;
use MakinaCorpus\Layout\Grid\HorizontalContainer;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Grid\VerticalContainer;
use MakinaCorpus\Layout\Render\BootstrapGridRenderer;
use MakinaCorpus\Layout\Render\RenderCollection;

/**
 * Bootstrap 3 compatible grid renderer.
 */
class EditLinksRendererDecorator extends BootstrapGridRenderer
{
    /**
     * @var Context
     */
    private $context;

    /**
     * Default constructor
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Render column
     *
     * @param string $innerText
     *
     * @return string
     */
    private function renderRow(string $innerText, string $identifier = null) : string
    {
        if ($identifier) {
            // @todo this should be escaped
            $additional = ' data-id="' . $identifier . '"';
            $container  = ' data-contains="1"';
        } else {
            $additional = '';
            $container  = '';
        }

        return <<<EOT
<div class="container-fluid"{$additional}>
  <div class="row"{$container}>
    {$innerText}
  </div>
</div>
EOT;
    }

    /**
     * Render column
     *
     * @param string[] $sizes
     *   An array of size, keys are media display identifiers mapping to
     *   bootstrap own prefixes (xs, sm, md, lg) and values are the width
     *   on the bootstrap grid for those medias.
     * @param string $innerText
     * @param string $identifier
     *
     * @return string
     */
    private function renderColumn(array $sizes, string $innerText, string $identifier = null) : string
    {
        $classes = [];
        foreach ($sizes as $media => $size) {
            $classes[] = 'col-' . $media . '-' . $size;
        }

        $classAttr = implode(' ', $classes);

        if ($identifier) {
            // @todo this should be escaped
            $additional = ' data-id="' . $identifier . '" data-contains="1"';
        } else {
            $additional = '';
        }

        return <<<EOT
<div class="{$classAttr}"{$additional}>
  {$innerText}
</div>
EOT;
    }

    /**
     * {@inheritdoc}
     */
    public function renderVerticalContainer(VerticalContainer $container, RenderCollection $collection) : string
    {
        if ($this->context->hasToken()) {
            $innerText = $this->renderMenu($this->getVerticalContainerButtons($container));
        } else {
            $innerText = '';
        }

        foreach ($container->getAllItems() as $child) {
            $innerText .= $collection->getRenderedItem($child);
        }

        return $this->renderRow($this->renderColumn(['md' => 12], $innerText, $collection->identify($container)));
    }

    /**
     * {@inheritdoc}
     */
    public function renderColumnContainer(ColumnContainer $container, RenderCollection $collection) : string
    {
        if ($this->context->hasToken()) {
            $innerText = $this->renderMenu($this->getColumnButtons($container));
        } else {
            $innerText = '';
        }

        foreach ($container->getAllItems() as $child) {
            $innerText .= $collection->getRenderedItem($child);
        }

        return $innerText;
    }

    /**
     * {@inheritdoc}
     */
    public function renderHorizontalContainer(HorizontalContainer $container, RenderCollection $collection) : string
    {
        // Do not display container options if they are children because
        // they will be merge to each child menu instead
        if ($this->context->hasToken()) {
            $innerText = $this->renderMenu($this->getHorizontalButtons($container));
        } else {
            $innerText = '';
        }

        // @todo find a generic way to push column sizes into configuration
        //   and the user customize it
        $innerContainers = $container->getAllItems();
        $defaultSize = floor(12 / count($innerContainers));

        foreach ($innerContainers as $child) {
            $innerText .= $this->renderColumn(['md' => $defaultSize], $collection->getRenderedItem($child), $collection->identify($child));
        }

        return $this->renderRow($innerText, $collection->identify($container));
    }

    private function renderMenu(array $links) : string
    {
        $title = t("Actions");
        $links = '<li>' . implode('</li><li>', $links) . '</li>';

        return <<<EOT
<div class="layout-menu">
  <a href="#" title="{$title}">
    <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
    <span class="sr-only">{$title}</span>
  </a>
  <ul>
    {$links}
  </ul>
</div>
EOT;
    }

    private function renderLink($title, $route, array $parameters, string $icon = null) : string
    {
        foreach ($parameters as $name => $value) {
            $search = '{' . $name . '}';
            if (false !== strpos($route, $search)) {
                $route = str_replace($search, $value, $route);
                unset($parameters[$name]);
            }
        }

        if ($icon) {
            $title = '<span class="glyphicon glyphicon-' . $icon . '" aria-hidden="true"></span> ' . $title;
        }

        return l($title, $route, ['query' => $parameters, 'html' => true]);
    }

    private function createOptions(ItemInterface $item, array $options) : array
    {
        return array_merge(drupal_get_destination(), [
            'tokenString' => $this->context->getCurrentToken()->getToken(),
            'layoutId' => $item->getLayoutId(),
        ], $options);
    }

    private function getColumnButtons(ColumnContainer $container) : array
    {
        $parentId = $container->getParent()->getStorageId();

        // Merge with parent options, visually it's better to hide the parent
        // menu and use its children to replicate its context
        return array_merge(
            $this->getVerticalContainerButtons($container),
            ['<li class="divider"></li>'],
            [
//             $this->renderLink(t('Preend item'), 'layout/ajax/{layout}/column/{id}/prepend', $options),
//             $this->renderLink(t('Append item'), 'layout/ajax/{layout}/column/{id}/prepend', $options),
                $this->renderLink(
                    t('Add column before'),
                    'layout/ajax/add-column',
                    $this->createOptions($container, [
                        'containerId' => $parentId,
                        'position' => 0, // @todo
                    ]),
                    'chevron-left'
                ),
                $this->renderLink(
                    t('Add column after'),
                    'layout/ajax/add-column',
                    $this->createOptions($container, [
                        'containerId' => $parentId,
                        'position' => 0, // @todo
                    ]),
                    'chevron-right'
                ),
                $this->renderLink(
                    t('Remove this column'),
                    'layout/ajax/remove-column',
                    $this->createOptions($container, [
                        'containerId' => $parentId,
                        'position' => 0, // @todo
                    ]),
                    'remove'
                ),
            ]
        );
    }

    private function getHorizontalButtons(HorizontalContainer $container) : array
    {
        return [
            $this->renderLink(
                t('Prepend column'),
                'layout/ajax/add-column',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => 0,
                ]),
                'chevron-left'
            ),
            $this->renderLink(
                t('Append column'),
                'layout/ajax/add-column',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => $container->count(),
                ]),
                'chevron-right'
            ),
            $this->renderLink(
                t('Remove'),
                'layout/ajax/remove',
                $this->createOptions($container, [
                    'itemId' => $container->getStorageId(),
                ]),
                'remove'
            ),
        ];
    }

    private function getVerticalContainerButtons(VerticalContainer $container) : array
    {
        return [
//             $this->renderLink(t('Prepend item'), 'layout/ajax/{layout}/vbox/{id}/prepend', $options),
//             $this->renderLink(t('Append item'), 'layout/ajax/{layout}/vbox/{id}/append', $options),
            $this->renderLink(
                t('Prepend column container'),
                'layout/ajax/add-column-container',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => 0,
                    'columnCount' => 2,
                ]),
                'th-large'
            ),
            $this->renderLink(
                t('Append column container'),
                'layout/ajax/add-column-container',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => $container->count(),
                    'columnCount' => 2,
                ]),
                'th-large'
            ),
            $this->renderLink(
                t('Remove'),
                'layout/ajax/remove',
                $this->createOptions($container, [
                    'itemId' => $container->getStorageId(),
                ]),
                'remove'
            ),
        ];
    }

    private function getItemButtons(ItemInterface $item) : array
    {
        return [
//             $this->renderLink(t('Add item before'), 'layout/ajax/{layout}/item/{id}/add-before', $options),
//             $this->renderLink(t('Add item after'), 'layout/ajax/{layout}/item/{id}/add-after', $options),
            $this->renderLink(
                t('Remove'),
                'layout/ajax/remove',
                $this->createOptions($item, [
                    'itemId' => $item->getStorageId(),
                ]),
                'remove'
            ),
        ];
    }
}
