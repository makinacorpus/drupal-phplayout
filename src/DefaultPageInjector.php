<?php

namespace MakinaCorpus\Drupal\Layout;

use MakinaCorpus\Drupal\Layout\Event\CollectLayoutEvent;
use MakinaCorpus\Drupal\Layout\Form\LayoutContextEditForm;
use MakinaCorpus\Drupal\Layout\Render\EditRendererDecorator;
use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Layout\Controller\Context;
use MakinaCorpus\Layout\Error\InvalidTokenError;
use MakinaCorpus\Layout\Render\Renderer;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var EditRendererDecorator
     */
    private $editGridRenderer;

    /**
     * @var LayoutStorageInterface
     */
    private $storage;

    /**
     * Default constructor
     *
     * @param Context $context
     * @param \DatabaseConnection $database
     * @param EventDispatcherInterface $eventDispatcher
     * @param Renderer $renderer
     * @param LayoutStorageInterface $storage
     * @param EditRendererDecorator $editGridRenderer
     */
    public function __construct(
        Context $context,
        \DatabaseConnection $database,
        EventDispatcherInterface $eventDispatcher,
        Renderer $renderer,
        LayoutStorageInterface $storage,
        EditRendererDecorator $editGridRenderer
    ) {
        $this->context  = $context;
        $this->database = $database;
        $this->eventDispatcher = $eventDispatcher;
        $this->renderer = $renderer;
        $this->storage  = $storage;
        $this->editGridRenderer = $editGridRenderer;
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
        $event = new CollectLayoutEvent($this->storage);
        $this->eventDispatcher->dispatch(CollectLayoutEvent::EVENT_NAME, $event);

        // Fetch all layouts and set them into the context
        $accessMap = $event->getAccessMap();
        foreach ($event->getLayouts() as $layout) {
            $this->context->add([$layout], $accessMap[$layout->getId()]);
        }

        // Load the token after we did loaded all the layouts, to ensure that
        // their temporary equivalents attached to the token will be reloaded
        // instead.
        $token = null;
        if ($tokenString = $request->get(PHP_LAYOUT_TOKEN_PARAMETER)) {
            try {
                $this->context->setCurrentToken($tokenString);
                $token = $this->context->getCurrentToken();
            } catch (InvalidTokenError $e) {
                // Fallback on non-edit mode
            }
        }

        // Working multiple pass version
        foreach ($this->context->getAll() as $layout) {
            if (!$layout instanceof Layout || !($region = $layout->getRegion())) {
                $region = 'content';
            }

            if ($token && $token->contains($layout)) {
                $this->editGridRenderer->setCurrentToken($token);
            } else {
                $this->editGridRenderer->dropToken();
            }

            $page[$region]['layout'][$layout->getId()] = ['#markup' => $this->renderer->render($layout->getTopLevelContainer(), $this->context)];
        }

        if ($this->context->containsEditableLayouts()) {

            if ($this->context->hasToken()) {
                drupal_add_library('phplayout', 'edit_basic');
                drupal_add_js([
                    'layout' => [
                        'token'   => $tokenString,
                        'baseurl' => base_path(),
                    ]
                ], 'setting');
            }

            if ($event->isFormEnabled()) {
                $page['content']['layout_edit_form'] = \Drupal::formBuilder()->getForm(LayoutContextEditForm::class);
                $page['content']['layout_edit_form']['#weight'] = -1000;
            }
        }
    }
}
