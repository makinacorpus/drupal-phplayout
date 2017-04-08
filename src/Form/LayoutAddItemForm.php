<?php

namespace MakinaCorpus\Drupal\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Layout\Controller\Context;
use MakinaCorpus\Layout\Controller\EditController;
use MakinaCorpus\Layout\Grid\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout context edit form
 */
class LayoutAddItemForm extends FormBase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var EditController
     */
    private $editController;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('php_layout.context'),
            $container->get('php_layout.edit_controller')
        );
    }

    /**
     * Default constructor
     *
     * @param Context $context
     * @param EditController $editController
     */
    public function __construct(Context $context, EditController $editController)
    {
        $this->context = $context;
        $this->editController = $editController;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'php_layout_add_item_form';
    }

    /**
     * Find all view modes
     *
     * @return string[]
     */
    private function findViewModes()
    {
        return variable_get('phplayout_node_view_modes', [
            ItemInterface::STYLE_DEFAULT => t("Teaser"),
            'full' => t("Full"),
        ]);
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState, string $tokenString = '', int $layoutId = 0, int $containerId = 0, int $position = 0)
    {
        if (!$tokenString || !$layoutId || !$containerId) {
            return $form;
        }

        // @todo for now only nodes are supported
        $formState->setTemporaryValue('item_type', 'node');
        $formState->setTemporaryValue('token', $tokenString);
        $formState->setTemporaryValue('layout_id', $layoutId);
        $formState->setTemporaryValue('container_id', $containerId);
        $formState->setTemporaryValue('position', $position);

        // Let's just provide a simple autocomplete field for now.
        $form['item_id'] = [
            '#title'          => t("Item"),
            '#type'           => 'textfield',
            '#description'    => t("This is an autocomplete field, please type at least 3 letters"),
            '#attributes'     => ['placeholder' => t("My article...")],
            '#required'       => true,
            '#autocomplete_path' => 'layout/callback/node-autocomplete',
        ];

        $form['style'] = [
            '#type'           => 'select',
            '#title'          => t("Style"),
            '#options'        => $this->findViewModes(),
            '#default_value'  => ItemInterface::STYLE_DEFAULT,
            '#required'       => true,
        ];

        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type'   => 'submit',
                '#value'  => t("Add item"),
                '#submit' => ['::addItemSubmit'],
            ],
            'cancel' => [
                '#type'   => 'submit',
                '#value'  => t("Cancel"),
                '#submit' => ['::cancelSubmit'],
            ],
        ];

        return $form;
    }

    /**
     * {inheritdoc}
     */
    public function addItemSubmit(array &$form, FormStateInterface $formState)
    {
        // Okay now breath
        $tokenString  = $formState->getTemporaryValue('token');
        $layoutId     = $formState->getTemporaryValue('layout_id');
        $containerId  = $formState->getTemporaryValue('container_id');
        $itemType     = $formState->getTemporaryValue('item_type');
        $itemId       = null;
        $style        = $formState->getValue('style');
        $position     = $formState->getTemporaryValue('position');

        $matches        = [];
        $unescapedInput = $formState->getValue('item_id');

        if (!preg_match('/\((\d+)\)$/', $unescapedInput, $matches)) {
            drupal_set_message(t("Wrong input"));
            return;
        }
        $itemId = $matches[1];

        $ret = $this->editController->addAction($tokenString, $layoutId, $containerId, $itemType, $itemId, $position, $style);

        if (empty($ret['success'])) {
            drupal_set_message(t("Wrong input"));
        }
    }

    /**
     * {inheritdoc}
     */
    public function cancelSubmit(array &$form, FormStateInterface $formState)
    {
        // Do nothing, let the form redirect upon the destination parameter.
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        // This will never be called.
    }
}
