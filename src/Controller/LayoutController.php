<?php

namespace MakinaCorpus\Drupal\Layout\Controller;

use MakinaCorpus\Layout\Controller\EditController;
use MakinaCorpus\Layout\Grid\ItemInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LayoutController extends EditController
{
    /**
     * Default constructor
     */
    public function __construct()
    {
        // @todo remove this, set the controller into the container instead
        parent::__construct(
            \Drupal::service('php_layout.token_storage'),
            \Drupal::service('php_layout.type_registry'),
            \Drupal::service('php_layout.renderer')
        );
    }

    private function handleResponse(array $ret) : Response
    {
        $request = \Drupal::request();

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
    public function removeAction(string $tokenString, int $layoutId, int $itemId)
    {
        return $this->handleResponse(
            parent::removeAction($tokenString, $layoutId, $itemId)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addColumnContainerAction(string $tokenString, int $layoutId, int $containerId, int $position = 0, int $columnCount = 2, string $style = ItemInterface::STYLE_DEFAULT)
    {
        return $this->handleResponse(
            parent::addColumnContainerAction($tokenString, $layoutId, $containerId, $position, $columnCount, $style)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addColumnAction(string $tokenString, int $layoutId, int $containerId, int $position = 0)
    {
        return $this->handleResponse(
            parent::addColumnAction($tokenString, $layoutId, $containerId, $position)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function removeColumnAction(string $tokenString, int $layoutId, int $containerId, int $position = 0)
    {
        return $this->handleResponse(
            parent::removeColumnAction($tokenString, $layoutId, $containerId, $position)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addAction(string $tokenString, int $layoutId, int $containerId, string $itemType, string $itemId, int $position = 0, string $style = ItemInterface::STYLE_DEFAULT)
    {
        return $this->handleResponse(
            parent::addAction($tokenString, $layoutId, $containerId, $itemType, $itemId, $position, $style)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function moveAction(string $tokenString, int $layoutId, int $containerId, int $itemId, int $newPosition)
    {
        return $this->handleResponse(
            parent::moveAction($tokenString, $layoutId, $containerId, $itemId, $newPosition)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function moveOutsideAction(string $tokenString)
    {
        return $this->handleResponse(
            parent::moveOutsideAction($tokenString)
        );
    }
}
