<?php

namespace MakinaCorpus\Drupal\Layout;

use MakinaCorpus\Drupal\Layout\Form\LayoutContextEditForm;
use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Layout\Controller\Context;
use MakinaCorpus\Layout\Error\InvalidTokenError;
use MakinaCorpus\Layout\Render\Renderer;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default object responsible for injecting layouts into pages during
 * hook_page_build() time, usable as-is but which probably should be
 * extended or replaced for a lot of sites.
 */
class DefaultPageInjector
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var LayoutStorageInterface
     */
    private $storage;

    /**
     * Default constructor
     *
     * @param Context $context
     * @param \DatabaseConnection $database
     * @param Renderer $renderer
     * @param LayoutStorageInterface $storage
     */
    public function __construct(Context $context, \DatabaseConnection $database, Renderer $renderer, LayoutStorageInterface $storage)
    {
        $this->context  = $context;
        $this->database = $database;
        $this->renderer = $renderer;
        $this->storage  = $storage;
    }

    /**
     * Get context
     *
     * @return Context
     */
    final protected function getContext() : Context
    {
        return $this->context;
    }

    /**
     * Get database
     *
     * @return \DatabaseConnection
     */
    final protected function getDatabase() : \DatabaseConnection
    {
        return $this->database;
    }

    /**
     * Get layout storage
     *
     * @return LayoutStorageInterface
     */
    final protected function getLayoutStorage() : LayoutStorageInterface
    {
        return $this->storage;
    }

    /**
     * Get page layouts identifier list
     *
     * Override this depending on your business.
     *
     * @return LayoutInterface[]
     */
    protected function getPageLayoutList() : array
    {
        if (arg(0) !== 'node' && arg(2)) {
            return [];
        }
        if (!$node = menu_get_object()) {
            return [];
        }

        $layoutIdList = $this->getDatabase()->query("select id from {layout} where node_id = ?", [$node->nid])->fetchCol();

        if ($layoutIdList) {
            $layouts = $this->getLayoutStorage()->loadMultiple($layoutIdList);
        } else {
            // Automatically creates new layout for node if none exist
            $layouts = [$this->getLayoutStorage()->create(['node_id' => $node->nid])];
        }

        return $layouts;
    }

    /**
     * Injects layouts into sites
     *
     * @param Request $request
     *   Incoming request
     * @param array $page
     *   Page from Drupal hook_page_build()
     */
    public function inject(Request $request, array &$page)
    {
        // Fetch all layouts for the node
        $layouts = $this->getPageLayoutList();

        $this->context->add($layouts, true /* @todo access checks on each layout */);

        // Load the token after we did loaded all the layouts, to ensure that
        // their temporary equivalents attached to the token will be reloaded
        // instead.
        if ($tokenString = $request->get(PHP_LAYOUT_TOKEN_PARAMETER)) {
            try {
                $this->context->setCurrentToken($tokenString);
            } catch (InvalidTokenError $e) {
                // Fallback on non-edit mode
            }
        }

        // Working multiple pass version
        foreach ($this->context->getAll() as $layout) {
            if (!$layout instanceof Layout || !($region = $layout->getRegion())) {
                $region = 'content';
            }
            $page[$region]['layout'][$layout->getId()] = ['#markup' => $this->renderer->render($layout->getTopLevelContainer())];
        }

        if ($this->context->containsEditableLayouts()) {
            if ($this->context->hasToken()) {
                $path = drupal_get_path('module', 'phplayout');
                drupal_add_css($path . '/public/edit.css');
                drupal_add_js($path . '/public/edit.js');
            }

            $page['content']['layout_edit_form'] = \Drupal::formBuilder()->getForm(LayoutContextEditForm::class);
            $page['content']['layout_edit_form']['#weight'] = -1000;
        }
    }
}
