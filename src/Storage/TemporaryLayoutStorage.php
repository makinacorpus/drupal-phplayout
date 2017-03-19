<?php

namespace MakinaCorpus\Drupal\Layout\Storage;

use Drupal\Core\Cache\CacheBackendInterface;
use MakinaCorpus\Layout\Error\GenericError;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;

/**
 * Layout database storage
 */
class TemporaryLayoutStorage implements LayoutStorageInterface
{
    /**
     * @var CacheBackendInterface
     */
    private $cache;

    /**
     * Default constructor
     *
     * @param CacheBackendInterface $cache
     */
    public function __construct(CacheBackendInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function load(int $id) : LayoutInterface
    {
        $list = $this->loadMultiple([$id]);

        if (!$list) {
            throw new GenericError(sprintf("layout with id %d does not exist", $id));
        }

        return reset($list);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(int $id) : bool
    {
        try {
            $this->load($id);
            return true;
        } catch (GenericError $e) {
            return false;
        }
    }

    /**
     * Get cache identifier for layout
     *
     * @param int $id
     *
     * @return string
     */
    private function getCacheId(int $id) : string
    {
        return 'layout:' . $id;
    }

    /**
     * Load multiple layouts
     *
     * @param int[] int $id
     *
     * @return LayoutInterface[]
     *   Same as load() but an array of it keyed by identifiers
     */
    public function loadMultiple(array $idList) : array
    {
        $ret = [];

        if (!$idList) {
            return $ret;
        }

        $cacheIdList = [];
        foreach ($idList as $id) {
            $cacheIdList[$id] = $this->getCacheId($id);
        }

        $cachedItems = $this->cache->getMultiple($cacheIdList);

        foreach ($cachedItems as $item) {
            if ($item->data) {
                if ($item->data instanceof Layout) {
                    $ret[$item->data->getId()] = $item->data;
                }
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id)
    {
        $this->cache->delete($this->getCacheId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function update(LayoutInterface $layout)
    {
        $this->cache->set($this->getCacheId($layout->getId()), $layout);
    }

    /**
     * {@inheritdoc}
     */
    public function create() : LayoutInterface
    {
        $layout = new Layout();

        $func = \Closure::bind(function (int $id) { $this->id = $id; }, $layout, $layout);
        $func((int)rand(0, 127) . uniqid());

        $this->update($layout);

        return $layout;
    }

    /**
     * {@inheritdoc}
     */
    public function resetCaches()
    {
    }
}
