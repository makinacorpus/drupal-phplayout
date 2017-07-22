<?php

namespace MakinaCorpus\Drupal\Layout\Event;

use MakinaCorpus\Layout\EventDispatcher\CollectLayoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds layout edit actions and form to UI.
 */
final class CollectLayoutEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CollectLayoutEvent::EVENT_NAME => [
                ['onCollectLayout', 0],
            ],
        ];
    }

    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    /**
     * Collects current page layout
     */
    public function onCollectLayout(CollectLayoutEvent $event)
    {
        if (arg(0) !== 'node' || arg(2)) {
            return [];
        }
        if (!$node = menu_get_object()) {
            return [];
        }

        $layoutIdList = $this->database->query("select id from {layout} where node_id = ?", [$node->nid])->fetchCol();

        if (!$layoutIdList) {
            // Automatically creates new layout for node if none exist
            $layoutIdList = [$event->getContext()->getLayoutStorage()->create(['node_id' => $node->nid])->getId()];
        }

        $event->addLayoutList($layoutIdList);
        // @todo set editable status from here?
    }
}
