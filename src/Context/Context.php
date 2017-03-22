<?php

namespace MakinaCorpus\Drupal\Layout\Context;

use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Drupal\Layout\Storage\TemporaryLayoutStorage;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;

/**
 * Handles a single layout current editable context
 */
final class Context
{
    use TokenAwareTrait;

    /**
     * AJAX security token parameter name
     */
    const PARAM_AJAX_TOKEN  = 'token';

    /**
     * Page context token parameter name
     */
    const PARAM_PAGE_TOKEN  = 'page-edit';

    /**
     * Site token parameter name
     */
    const PARAM_SITE_TOKEN  = 'site-edit';

    /**
     * @var LayoutStorageInterface
     */
    private $storage;

    /**
     * @var TemporaryLayoutStorage
     */
    private $temporaryStorage;

    /**
     * @var Layout[]
     */
    private $layouts = [];

    /**
     * Default constructor
     *
     * @param LayoutStorageInterface $storage
     * @param TemporaryLayoutStorage $temporaryStorage
     */
    public function __construct(LayoutStorageInterface $storage, TemporaryLayoutStorage $temporaryStorage)
    {
        $this->storage = $storage;
        $this->temporaryStorage = $temporaryStorage;
    }

    /**
     * Commit session changes and restore storage
     */
    public function commit()
    {
        // Throws a nice exception if no token
        $this->getToken();

        if (!$this->layouts) {
            throw new \LogicException("No contextual instance is set, cannot commit");
        }

        foreach ($this->layouts as $layout) {
            $this->temporaryStorage->delete($layout->getId());
            $this->storage->update($layout);
        }

        $this->setToken(null);
    }

    /**
     * Rollback session changes and restore storage
     */
    public function rollback()
    {
        // Throws a nice exception if no token
        $this->getToken();

        if (!$this->layout) {
            throw new \LogicException("No contextual instance is set, cannot commit");
        }

        $idList = [];
        foreach ($this->layouts as $layout) {
            $this->temporaryStorage->delete($idList[] = $layout->getId());
        }

        $this->setToken(null);

        // Reload the real layouts unchanged
        $this->layouts = $this->storage->loadMultiple($idList);
    }

    /**
     * Set current layouts for this context
     *
     * @param int[] $idList
     */
    public function setLayoutIdentifiers(array $idList)
    {
        if ($this->layouts) {
            throw new \LogicException("You cannot change layouts once set");
        }

        $this->layouts = $this->getStorage()->loadMultiple($idList);
    }

    /**
     * Get the current context layout
     *
     * @return Layout[]
     */
    public function getLayouts() : array
    {
        return $this->layouts;
    }

    /**
     * Does this context has a temporary token
     *
     * @return bool
     */
    public function isTemporary() : bool
    {
        return $this->hasToken();
    }

    /**
     * Get current storage, temporary if editable, persistent if not
     *
     * @return LayoutStorageInterface
     */
    public function getStorage()
    {
        if ($this->hasToken()) {
            $this->temporaryStorage->setToken($this->getToken());

            return $this->temporaryStorage;
        }

        return $this->storage;
    }
}
