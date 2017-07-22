<?php

namespace MakinaCorpus\Drupal\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Layout\Context\EditToken;
use MakinaCorpus\Layout\Error\GenericError;
use MakinaCorpus\Layout\Grid\ContainerInterface as LayoutContainerInterface;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Render\GridRendererInterface;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use MakinaCorpus\Layout\Storage\TokenLayoutStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout context edit form
 */
class LayoutItemOptionsForm extends FormBase
{
    /**
     * @var TokenLayoutStorageInterface
     */
    private $tokenStorage;

    /**
     * @var GridRendererInterface
     */
    private $gridRenderer;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('php_layout.token_storage'),
            $container->get('php_layout.grid_renderer')
        );
    }

    /**
     * Default constructor
     *
     * @param TokenLayoutStorageInterface $tokenStorage
     */
    public function __construct(TokenLayoutStorageInterface $tokenStorage, GridRendererInterface $gridRenderer)
    {
        $this->tokenStorage = $tokenStorage;
        $this->gridRenderer = $gridRenderer;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'php_layout_item_options_form';
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
    public function buildForm(array $form, FormStateInterface $formState, EditToken $token = null, LayoutInterface $layout = null, int $itemId = 0)
    {
        if (!$token || !$layout || !$itemId) {
            return $form;
        }

        try {
            $layout = $this->tokenStorage->load($token->getToken(), $layout->getId());
            $item   = $layout->findItem($itemId);
        } catch (GenericError $e) {
            return;
        }

        // @todo for now only nodes are supported
        $formState->setTemporaryValue('token', $token);
        $formState->setTemporaryValue('layout', $layout);
        $formState->setTemporaryValue('item', $item);

        if ('node' === $item->getType()) {
            $form['style'] = [
                '#type'           => 'select',
                '#title'          => t("Style"),
                '#options'        => $this->findViewModes(),
                '#default_value'  => $item->getStyle(),
                '#required'       => true,
            ];
        } else if (LayoutContainerInterface::VERTICAL_CONTAINER === $item->getType()) {
            $form['style'] = [
                '#type'           => 'select',
                '#title'          => t("Style"),
                '#options'        => $this->gridRenderer->getColumnStyles(),
                '#default_value'  => $item->getStyle(),
                '#required'       => true,
            ];
        }

        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type'   => 'submit',
                '#value'  => t("Save options"),
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
        $token  = $formState->getTemporaryValue('token');
        $layout = $formState->getTemporaryValue('layout');
        $item   = $formState->getTemporaryValue('item');

        /** @var \MakinaCorpus\Layout\Grid\ItemInterface $item */
        $item->setStyle($formState->getValue('style'));

        $this->tokenStorage->update($token->getToken(), $layout);
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
