<?php

namespace MakinaCorpus\Drupal\Layout\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
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
     * Default constructor
     */
    public function __construct(EditController $controller)
    {
        $this->controller = $controller;
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
     * {@inheritdoc}
     */
    public function removeAction(Request $request, string $tokenString, int $layoutId, int $itemId) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->removeAction($tokenString, $layoutId, $itemId)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addColumnContainerAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0, int $columnCount = 2, string $style = ItemInterface::STYLE_DEFAULT) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->addColumnContainerAction($tokenString, $layoutId, $containerId, $position, $columnCount, $style)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addColumnAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->addColumnAction($tokenString, $layoutId, $containerId, $position)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function removeColumnAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $position = 0) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->removeColumnAction($tokenString, $layoutId, $containerId, $position)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addAction(Request $request, string $tokenString, int $layoutId, int $containerId, string $itemType, string $itemId, int $position = 0, string $style = ItemInterface::STYLE_DEFAULT) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->addAction($tokenString, $layoutId, $containerId, $itemType, $itemId, $position, $style)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function moveAction(Request $request, string $tokenString, int $layoutId, int $containerId, int $itemId, int $newPosition) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->moveAction($tokenString, $layoutId, $containerId, $itemId, $newPosition)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function moveOutsideAction(Request $request, string $tokenString) : Response
    {
        return $this->handleResponse(
            $request,
            $this->controller->moveOutsideAction($tokenString)
        );
    }
}
