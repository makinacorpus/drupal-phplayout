<?php

namespace MakinaCorpus\Drupal\Layout\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use MakinaCorpus\Drupal\Layout\Form\LayoutAddItemForm;
use MakinaCorpus\Drupal\Layout\Form\LayoutItemOptionsForm;
use MakinaCorpus\Drupal\Layout\Render\EditRendererDecorator;
use MakinaCorpus\Drupal\Sf\DrupalResponse;
use MakinaCorpus\Layout\Context\Context;
use MakinaCorpus\Layout\Context\EditToken;
use MakinaCorpus\Layout\Controller\EditController;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MakinaCorpus\Layout\Type\ItemTypeRegistry;
use MakinaCorpus\Layout\Render\Renderer;

/**
 * Drupal oriented layout actions controller
 */
class LayoutController extends EditController
{
    private $drupalFormBuilder;
    private $database;
    private $editGridRenderer;

    /**
     * Default constructor
     */
    public function __construct(FormBuilderInterface $drupalFormBuilder, \DatabaseConnection $database, EditRendererDecorator $editGridRenderer, Renderer $renderer, ItemTypeRegistry $typeRegistry)
    {
        parent::__construct($typeRegistry, $renderer);

        $this->drupalFormBuilder = $drupalFormBuilder;
        $this->database = $database;
        $this->editGridRenderer = $editGridRenderer;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleResponse(Request $request, array $ret)
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($ret);
        }

        if (($url = $request->get('destination')) && !url_is_external($url)) {

            $parsed = drupal_parse_url($url);
            $path = $parsed['path'];

            $options = [];
            $options['query'] = $parsed['query'];
            $options['fragment'] = $parsed['fragment'];

            return new RedirectResponse(url($path, $options));
        }

        return new Response();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareResponse(Request $request, Context $context, EditToken $token)
    {
        // Force context to be set in AJAX queries in order for the rendering
        // to include all edit links.
        if ($request->isXmlHttpRequest()) {
            $this->editGridRenderer->setCurrentToken($context->getToken());
        }
    }

    /**
     * Add item form action
     */
    public function addItemFormAction(Request $request, Context $context, LayoutInterface $layout, int $containerId, int $position = 0) : Response
    {
        $response = new DrupalResponse();
        $response->setContent($this->drupalFormBuilder->getForm(LayoutAddItemForm::class, $context, $layout, $containerId, $position));

        return $response;
    }

    /**
     * Edit item form action
     */
    public function editItemFormAction(Request $request, Context $context, LayoutInterface $layout, int $itemId = 0)
    {
        $response = new DrupalResponse();
        $response->setContent($this->drupalFormBuilder->getForm(LayoutItemOptionsForm::class, $context, $layout, $itemId));

        return $response;
    }

    /**
     * Set page content here action
     */
    public function setPageAction(Request $request, Context $context, EditToken $token, LayoutInterface $layout, int $containerId, int $position = 0) : Response
    {
        return $this->addAction($request, $context, $token, $layout, $containerId, 'page', 1, $position);
    }

    /**
     * Node autocomplete callback
     */
    public function nodeAutocompleteAction(string $string) : Response
    {
        $ret = [];

        $escapedString = $this->database->escapeLike($string) . '%';

        $results = $this
            ->database
            ->select('node', 'n')
            ->fields('n', ['nid', 'title', 'type'])
            ->condition('n.title', $escapedString, 'LIKE')
            ->addTag('node_access')
            ->execute()
            ->fetchAll()
        ;

        foreach ($results as $data) {

            $type = node_type_get_name($data->type);
            $key  = check_plain($type) . ' - ' . check_plain($data->title) . ' (' . $data->nid . ')';

            $ret[$key] = $key;
        }

        return new JsonResponse($ret);
    }
}
