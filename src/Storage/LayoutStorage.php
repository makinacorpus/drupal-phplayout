<?php

namespace MakinaCorpus\Drupal\Layout\Storage;

use MakinaCorpus\Layout\Error\GenericError;
use MakinaCorpus\Layout\Grid\ColumnContainer;
use MakinaCorpus\Layout\Grid\ContainerInterface;
use MakinaCorpus\Layout\Grid\HorizontalContainer;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Grid\VerticalContainer;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;
use MakinaCorpus\Layout\Type\ItemTypeRegistry;

/**
 * Layout database storage
 */
class LayoutStorage implements LayoutStorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * @var ItemTypeRegistry
     */
    private $typeRegistry;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     * @param ItemTypeRegistry $typeRegistry
     */
    public function __construct(\DatabaseConnection $database, ItemTypeRegistry $typeRegistry)
    {
        $this->database = $database;
        $this->typeRegistry = $typeRegistry;
    }

    /**
     * Create instance from database row
     *
     * @param \stdClass $item
     *   Item from database
     * @param array $options
     *   Arbitrary item options
     *
     * @return ItemInterface
     */
    private function populateLayoutCreateInstance(\stdClass $item, array $options) : ItemInterface
    {
        switch ($item->item_type) {

            case HorizontalContainer::HORIZONTAL_CONTAINER:
                return new HorizontalContainer($item->item_id);

            case VerticalContainer::VERTICAL_CONTAINER:
                return new VerticalContainer($item->item_id);

            default:
                return $this->typeRegistry->getType($item->item_type)->create($item->item_id, $item->style, $options);
        }
    }

    /**
     * Populate a single layout grid
     *
     * @param Layout $layout
     * @param array $items
     */
    private function populateLayout(Layout $layout, array $items)
    {
        // As said in other methods, our item list is flat and ordered, we now
        // can process it in a top-bottom order, create containers then their
        // children, and populate the tree. For this we need a flat reference
        // of items.
        $loaded = [];
        $toplevel = $layout->getTopLevelContainer();
        $layoutId = $layout->getId();

        foreach ($items as $item) {
            $instance = null;

            $options = null;
            if ($item->options) {
                $options = @unserialize($item->options);
                if (!$options) {
                    $options = [];
                }
            } else {
                $options = [];
            }

            if ($item->parent_id) {
                // Definition of items, for some reason, could be broken too,
                // in that specific case, drop silently the item
                // @todo constraint should be in database
                if (!isset($loaded[$item->parent_id])) {
                    continue;
                }

                $parent = $loaded[$item->parent_id];

                if ($parent instanceof HorizontalContainer) {
                    $instance = $parent->createColumnAt($item->position, $item->item_id);
                    $instance->setStorageId($layoutId, $item->id, true);
                } else {

                    $instance = $this->populateLayoutCreateInstance($item, $options);
                    $instance->setStorageId($layoutId, $item->id, true);

                    if ($parent instanceof ColumnContainer) {
                        $parent->addAt($instance, $item->position);
                        $parent->toggleUpdateStatus(false);
                    } else if ($parent instanceof VerticalContainer) {
                        $parent->addAt($instance, $item->position);
                        $parent->toggleUpdateStatus(false);
                    }
                }
            } else {
                $instance = $this->populateLayoutCreateInstance($item, $options);
                $instance->setStorageId($layoutId, $item->id, true);
                $toplevel->addAt($instance, $item->position);
            }

            if ($instance) {
                $loaded[$instance->getStorageId()] = $instance;
            }
        }
    }

    /**
     * Populate a set of layout grids
     *
     * @param Layout[] $layouts
     * @param array $items
     */
    private function populateLayoutAll(array $layouts, array $items)
    {
        $layoutId = null;
        $current = [];

        // Items are ordered using the layout identifiers, parent identifier
        // then position, which means we can treat them sequentially
        foreach ($items as $item) {
            if (!$layoutId) {
                $layoutId = $item->layout_id;
            } else if ($item->layout_id != $layoutId) {

                // End of previous layout, save previous one
                $this->populateLayout($layouts[$layoutId], $current);

                // Then jump to the next one and start a new queue
                $current = [];
                $layoutId = $item->layout_id;
            }

            $current[] = $item;
        }

        // Populate the last one
        if ($layoutId && $current) {
            $this->populateLayout($layouts[$layoutId], $current);
        }
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
        return (bool)$this
            ->database
            ->query("select 1 from layout where id = ?", [$id])
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function listWithConditions(array $conditions) : array
    {
        $query = $this
            ->database
            ->select('layout', 'l')
            ->fields('l', ['id'])
        ;

        if (!$conditions) {
            throw new GenericError("querying layouts with no conditions is stupid");
        }

        foreach ($conditions as $key => $value) {
            switch ($key) {

                case 'node_id':
                    if (null === $value || '' === $value) {
                        $query->isNull('l.node_id');
                    } else {
                        $query->condition('l.node_id', $value);
                    }
                    break;

                case 'site_id':
                    if (null === $value || '' === $value) {
                        $query->isNull('l.site_id');
                    } else {
                        $query->condition('l.site_id', $value);
                    }
                    break;

                case 'region':
                    if (null === $value || '' === $value) {
                        $query->isNull('l.region');
                    } else {
                        $query->condition('l.region', $value);
                    }
                    break;

                default:
                    throw new GenericError(sprintf("querying layouts with column '%s' is not possible", $key));
            }
        }

        return $query->execute()->fetchCol();
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

        // First load layouts
        $result = $this
            ->database
            ->query(
                "select id, node_id as nodeId, site_id as siteId, region from {layout} where id in (:list)",
                [':list' => $idList],
                ['fetch' => Layout::class]
            )
        ;

        /** @var \MakinaCorpus\Drupal\Layout\Storage\Layout $layout */
        foreach ($result as $layout) {
            $ret[$layout->getId()] = $layout;
        }

        // Load could have failed
        // @todo deal with loading errors when multiple?
        if (!$ret) {
            return $ret;
        }

        // Then load and populate their grid
        // @todo mysql will always null first, but on postgresql we need to
        //   set "nulls first" onto "parent_id asc nulls first".
        $items = $this
            ->database
            ->query(
                "
                    select d.*
                    from {layout_data} d
                    where
                        layout_id in (:list)
                    order by
                        layout_id asc,
                        parent_id asc,
                        position asc
                ",
                [':list' => array_keys($ret)]
            )
            ->fetchAll()
        ;

        $this->populateLayoutAll($ret, $items);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id)
    {
        $this->database->query("delete from {layout} where id = ?", [$id]);
    }

    /**
     * Update recursively items
     *
     * @param int $layoutId
     *   Layout identifier we're recursing into
     * @param ItemInterface $item
     *   Item to update or insert
     * @param int $position
     *   Because position is relative to parent
     * @param int[] $done
     *   Circular dependency breaker
     * @param int $parentId
     *   Parent identifier, if null, item will not be saved because this
     *   means we are processing the top level element, in our storage
     *   the top level element is virtual
     */
    private function updateRecursion(int $layoutId, ItemInterface $item, int $position, array &$done, int $parentId = -1)
    {
        $id = $item->getStorageId() ?: 0;

        // Circular dependency breaker
        if (isset($done[$id])) {
            return;
        }

        // Update the item into database
        if ($parentId != -1) {
            if ($item->isPermanent()) {
                if (!$id) {
                    throw new GenericError(sprintf("Item cannot be permanent without an identifier"));
                }
                if ($item->isUpdated()) {
                    $this
                        ->database
                        ->update('layout_data')
                        ->fields([
                            'parent_id' => $parentId ? $parentId : null,
                            'layout_id' => $layoutId, // not mandatory
                            'item_type' => $item->getType(),
                            'item_id'   => $item->getId(),
                            'style'     => $item->getStyle(),
                            'position'  => $position,
                            'options'   => null, // @todo
                        ])
                        ->condition('id', $id)
                        ->execute()
                    ;
                }
            } else {
                $id = $this
                    ->database
                    ->insert('layout_data')
                    ->fields([
                        'parent_id' => $parentId ? $parentId : null,
                        'layout_id' => $layoutId,
                        'item_type' => $item->getType(),
                        'item_id'   => $item->getId(),
                        'style'     => $item->getStyle(),
                        'position'  => $position,
                        'options'   => null, // @todo
                    ])
                    ->execute()
                ;

                $item->setStorageId($layoutId, $id, true);
            }
        }

        if ($id) {
            $done[$id] = $id;
            $item->toggleUpdateStatus(false);
        }

        // This is a top-bottom traversal, we need containers to be saved
        // before their children
        if ($item instanceof ContainerInterface) {
            foreach ($item->getAllItems() as $position => $child) {
                $this->updateRecursion($layoutId, $child, $position, $done, $id);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(LayoutInterface $layout)
    {
        // This is where things go wild, we need to top-bottom go through all
        // the tree, save updated elements and insert new one, potentially
        // delete deleted ones.
        $done = [];

        $transaction = null;

        try {
            $transaction = $this->database->startTransaction();

            $this->updateRecursion($layout->getId(), $layout->getTopLevelContainer(), 0, $done);

            // Now that we have saved pretty much everything, remove non existing
            // items, all those that have not been traversed.
            if ($done) {
                $this
                    ->database
                    ->delete('layout_data')
                    ->condition('layout_id', $layout->getId())
                    ->condition('id', $done, 'not in')
                    ->execute()
                ;
            }

            unset($transaction); // Explicit commit

        } catch (\Throwable $e) {
            try {
                if ($transaction) {
                    $transaction->rollback();
                }
            } catch (\Throwable $e2) {
                // You're fucked.
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $values = []) : LayoutInterface
    {
        foreach (array_keys($values) as $key) {
            switch ($key) {

                case 'node_id':
                case 'site_id':
                case 'region':
                    break;

                default:
                    throw new GenericError(sprintf("inserting layouts with column '%s' is not possible", $key));
            }
        }

        if ($values) {
            $id = (int)$this
                ->database
                ->insert('layout')
                ->fields($values)
                ->execute()
            ;
        } else {
            $id = (int)$this
                ->database
                ->query("insert into layout () values ()", [], ['return' => \Database::RETURN_INSERT_ID])
            ;
        }

        return $this->load($id);
    }

    /**
     * {@inheritdoc}
     */
    public function resetCaches()
    {
    }
}
