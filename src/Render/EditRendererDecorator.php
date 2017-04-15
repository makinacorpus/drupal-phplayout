<?php

namespace MakinaCorpus\Drupal\Layout\Render;

use MakinaCorpus\Layout\Controller\EditToken;
use MakinaCorpus\Layout\Grid\ColumnContainer;
use MakinaCorpus\Layout\Grid\ContainerInterface;
use MakinaCorpus\Layout\Grid\HorizontalContainer;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Grid\TopLevelContainer;
use MakinaCorpus\Layout\Render\GridRendererInterface;
use MakinaCorpus\Layout\Render\RenderCollection;

/**
 * Decorates another rendererd, and injects edit links when necessary.
 */
class EditRendererDecorator implements GridRendererInterface
{
    /**
     * @var GridRendererInterface
     */
    private $nested;

    /**
     * @var EditToken
     */
    private $token;

    /**
     * Default constructor
     *
     * @param GridRendererInterface $nested
     */
    public function __construct(GridRendererInterface $nested)
    {
        $this->nested = $nested;
    }

    /**
     * Allow changing context
     *
     * @todo find another way
     *
     * @param EditToken $token
     */
    public function setCurrentToken(EditToken $token)
    {
        $this->token = $token;
    }

    /**
     * Drop current token
     */
    public function dropToken()
    {
        $this->token = null;
    }

    /**
     * {@inheritdoc}
     */
    public function renderTopLevelContainer(TopLevelContainer $container, string $innerHtml) : string
    {
        if ($this->token) {
            $addition = $this->renderMenu($container, $this->getTopLevelContainerButtons($container));
        } else {
            $addition = '';
        }

        return $this->nested->renderTopLevelContainer($container, $addition . $innerHtml);
    }

    /**
     * {@inheritdoc}
     */
    public function renderColumnContainer(ColumnContainer $container, string $innerHtml) : string
    {
        if ($this->token) {
            $addition = $this->renderMenu($container, $this->getColumnButtons($container));
        } else {
            $addition = '';
        }

        return $this->nested->renderColumnContainer($container, $addition . $innerHtml);
    }

    /**
     * {@inheritdoc}
     */
    public function renderHorizontalContainer(HorizontalContainer $container, array $columnsHtml) : string
    {
        if ($this->token) {
            $addition = $this->renderMenu($container, $this->getHorizontalButtons($container));
        } else {
            $addition = '';
        }

        return $this->injectHtml(
            $this->nested->renderHorizontalContainer($container, $columnsHtml),
            $addition
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderItem(ItemInterface $item, ContainerInterface $parent, string $innerHtml, int $position) : string
    {
        $rendered = $this->nested->renderItem($item, $parent, $innerHtml, $position);

        if (!$this->token) {
            return $rendered;
        }

        if (!$rendered) {
            $rendered = '<p class="text-danger">' . t("Broken or missing item") . '</span>';
        }

        if (!$item instanceof ContainerInterface) {
            $rendered = '<div data-id="' . $item->getGridIdentifier() . '" data-item>' . $this->renderMenu($item, $this->getItemButtons($item, $parent, $position)) . $rendered . '</div>';
        }

        return $rendered;
    }

    /**
     * Arbitrary inject HTML into the first div found
     *
     * @param string $input
     *   Rendered HTML item from the nested renderer
     * @param string $addition
     *   HTML to inject
     *
     * @return string
     *   Rendered HTML with injected content
     */
    private function injectHtml(string $input, string $addition) : string
    {
        // @todo
        //   - do this a better way
        //   - it might break HTML if there's ">" in the attributes

        $index = strpos($input, '>');

        if (false === $index) {
            return $addition . $input;
        }

        return substr_replace($input, $addition, $index + 1, 0);
    }

    /**
     * Render action menu
     *
     * @param ItemInterface $item
     * @param string[] $links
     *
     * @return string
     */
    private function renderMenu(ItemInterface $item, array $links) : string
    {
        if ($item instanceof ColumnContainer) {
            $title = t("Column");
        } else if ($item instanceof HorizontalContainer) {
            $title = t("Columns container");
        } else if ($item instanceof TopLevelContainer) {
            $title = t("Top level container");
        } else {
            $title = $item->getTitle() ?: t("Item");
        }
        $links = '<li>' . implode('</li><li>', $links) . '</li>';

        return <<<EOT
<div class="layout-menu">
  <a href="#" title="{$title}">
    <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
    <span class="title">{$title}</span>
  </a>
  <ul>
    {$links}
  </ul>
</div>
EOT;
    }

    /**
     * Render a single action link
     *
     * @param string $title
     *   Link title, must already be localized if necessary
     * @param string $route
     *   Framework route
     * @param string[] $parameters
     *   Route parameters
     * @param string $icon
     *   Link glyphicon identifier
     * @param bool $disabled
     *   Is this links disabled
     *
     * @return string
     */
    private function renderLink(string $title, string $route, array $parameters, string $icon = null, bool $disabled = false) : string
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

        $options = ['query' => $parameters, 'html' => true];

        if ($disabled) {
            $options['attributes']['disabled'] = 'true';
            $options['attributes']['class'][] = 'disabled';
        }

        return l($title, $route, $options);
    }

    /**
     * Create edit link options
     *
     * @param ItemInterface $item
     *   Item to work with
     * @param array $options
     *   Custom options depending on the link
     *
     * @return array
     *   Options array with token and layout information added
     */
    private function createOptions(ItemInterface $item, array $options) : array
    {
        return array_merge(drupal_get_destination(), [
            'tokenString' => $this->token->getToken(),
            'layoutId' => $item->getLayoutId(),
        ], $options);
    }

    private function getColumnButtons(ColumnContainer $container) : array
    {
        $parent   = $container->getParent();
        $parentId = $parent->getStorageId();
        $index    = $parent->getIndexOf($container);

        // Merge with parent options, visually it's better to hide the parent
        // menu and use its children to replicate its context
        return [
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
            '<li class="divider"></li>',
            $this->renderLink(
                t("Prepend item"),
                'layout/callback/add-item',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => 0,
                ]),
                'picture'
            ),
            $this->renderLink(
                t("Append item"),
                'layout/callback/add-item',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => $container->count(),
                ]),
                'picture'
            ),
            '<li class="divider"></li>',
            $this->renderLink(
                t("Set page content here"),
                'layout/callback/set-page',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => 0,
                ]),
                'star'
            ),
            '<li class="divider"></li>',
            $this->renderLink(
                t('Add column before'),
                'layout/ajax/add-column',
                $this->createOptions($container, [
                    'containerId' => $parentId,
                    'position' => $index,
                ]),
                'chevron-left'
            ),
            $this->renderLink(
                t('Add column after'),
                'layout/ajax/add-column',
                $this->createOptions($container, [
                    'containerId' => $parentId,
                    'position' => $index + 1,
                ]),
                'chevron-right'
            ),
            $this->renderLink(
                t('Remove this column'),
                'layout/ajax/remove-column',
                $this->createOptions($container, [
                    'containerId' => $parentId,
                    'position' => $index,
                ]),
                'remove'
            ),
        ];
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

    private function getTopLevelContainerButtons(TopLevelContainer $container) : array
    {
        return [
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
            '<li class="divider"></li>',
            $this->renderLink(
                t("Prepend item"),
                'layout/callback/add-item',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => 0,
                ]),
                'picture'
            ),
            $this->renderLink(
                t("Append item"),
                'layout/callback/add-item',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => $container->count(),
                ]),
                'picture'
            ),
            '<li class="divider"></li>',
            $this->renderLink(
                t("Set page content here"),
                'layout/callback/set-page',
                $this->createOptions($container, [
                    'containerId' => $container->getStorageId(),
                    'position' => 0,
                ]),
                'star'
            ),
        ];
    }

    private function getItemButtons(ItemInterface $item, ContainerInterface $parent, int $currentPosition) : array
    {
        return [
            $this->renderLink(
                t('Move to top'),
                'layout/ajax/move',
                $this->createOptions($item, [
                    'itemId' => $item->getStorageId(),
                    'containerId' => $parent->getStorageId(),
                    'newPosition' => 0,
                ]),
                'chevron-up',
                0 === $currentPosition
            ),
            $this->renderLink(
                t('Move to bottom'),
                'layout/ajax/move',
                $this->createOptions($item, [
                    'itemId' => $item->getStorageId(),
                    'containerId' => $parent->getStorageId(),
                    'newPosition' => $parent->count(),
                ]),
                'chevron-down',
                $parent->count() === $currentPosition
            ),
            $this->renderLink(
                t('Options'),
                'layout/callback/edit-item',
                $this->createOptions($item, [
                    'itemId' => $item->getStorageId(),
                ]),
                'cog'
            ),
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
