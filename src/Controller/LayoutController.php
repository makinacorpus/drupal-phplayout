<?php

namespace MakinaCorpus\Drupal\Layout\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use MakinaCorpus\Drupal\Layout\Form\LayoutAddItemForm;
use MakinaCorpus\Drupal\Layout\Form\LayoutItemOptionsForm;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Drupal\Sf\DrupalResponse;
use MakinaCorpus\Layout\Controller\Context;
use MakinaCorpus\Layout\Controller\EditController;
use MakinaCorpus\Layout\Grid\ItemInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drupal oriented layout actions controller
 */
class LayoutController extends Controller
{
    /**
     * @var EditController
     */
    private $controller;

    /**
     * @var FormBuilderInterface
     */
    private $drupalFormBuilder;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * Default constructor
     */
    public function __construct(
        EditController $controller,
        FormBuilderInterface $drupalFormBuilder,
        Context $context,
        \DatabaseConnection $database)
    {
        $this->controller = $controller;
        $this->drupalFormBuilder = $drupalFormBuilder;
        $this->context = $context;
        $this->database = $database;
    }

    /**
     * Handle edit controller response
     *
     * @param Request $request
     * @param array $ret
     *
     * @return Response
     */
    private function handleResponse(Request $request, array $ret) : Response
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
     * Add item form action
     */
    public function addItemFormAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0) : Response
    {
        $this->controller->loadLayoutOrDie($tokenString, $layoutId);

        $response = new DrupalResponse();
        $response->setContent($this->drupalFormBuilder->getForm(LayoutAddItemForm::class, $tokenString, $layoutId, $containerId, $position));

        return $response;
    }

    /**
     * Edit item form action
     */
    public function editItemFormAction(Request $request, string $tokenString, int $layoutId, int $itemId = 0)
    {
        $this->controller->loadLayoutOrDie($tokenString, $layoutId);

        $response = new DrupalResponse();
        $response->setContent($this->drupalFormBuilder->getForm(LayoutItemOptionsForm::class, $tokenString, $layoutId, $itemId));

        return $response;
    }

    /**
     * Set page content here action
     */
    public function setPageAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->addAction($tokenString, $layoutId, $containerId, 'page', 1, $position)
        );
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

    /**
     * Remove item action
     */
    public function removeAction(Request $request, string $tokenString, int $layoutId, int $itemId) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->removeAction($tokenString, $layoutId, $itemId)
        );
    }

    /**
     * Add horizontal container action
     */
    public function addColumnContainerAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0, int $columnCount = 2, string $style = ItemInterface::STYLE_DEFAULT) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->addColumnContainerAction($tokenString, $layoutId, $containerId, $position, $columnCount, $style)
        );
    }

    /**
     * Add column action
     */
    public function addColumnAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->addColumnAction($tokenString, $layoutId, $containerId, $position)
        );
    }

    /**
     * Remove column action
     */
    public function removeColumnAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->removeColumnAction($tokenString, $layoutId, $containerId, $position)
        );
    }

    /**
     * Add item action
     */
    public function addAction(Request $request, string $tokenString, int $layoutId, int $containerId, string $itemType, string $itemId, int $position = 0, string $style = ItemInterface::STYLE_DEFAULT) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->addAction($tokenString, $layoutId, $containerId, $itemType, $itemId, $position, $style)
        );
    }

    /**
     * Move item action
     */
    public function moveAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $itemId, int $newPosition) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->moveAction($tokenString, $layoutId, $containerId, $itemId, $newPosition)
        );
    }

    /**
     * Move item to another layout action
     */
    public function moveOutsideAction(Request $request, string $tokenString) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->moveOutsideAction($tokenString)
        );
    }
}
