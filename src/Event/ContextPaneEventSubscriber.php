<?php

namespace MakinaCorpus\Drupal\Layout\Event;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Drupal\Layout\Controller\AdminController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MakinaCorpus\Layout\Context\Context;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContextPaneEvent::EVENT_INIT => [
                ['onContextPaneInit', 0],
            ],
        ];
    }

    private $adminController;
    private $context;

    /**
     * Default constructor
     */
    public function __construct(AdminController $adminController, Context $context)
    {
        $this->adminController = $adminController;
        $this->context = $context;
    }

    /**
     * @param ContextPaneEvent $event
     */
    public function onContextPaneInit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();

        $contextPane->addTab('layout', $this->t("Layout"), 'th');
        $contextPane->add(['#markup' => $this->adminController->layoutSummaryAction(\Drupal::request(), $this->context)->getContent()], 'layout');
    }
}
