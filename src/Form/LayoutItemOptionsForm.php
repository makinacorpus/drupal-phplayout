<?php

namespace MakinaCorpus\Drupal\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Layout\Context\Context;
use MakinaCorpus\Layout\Context\EditToken;
use MakinaCorpus\Layout\Error\GenericError;
use MakinaCorpus\Layout\Grid\ContainerInterface as LayoutContainerInterface;
use MakinaCorpus\Layout\Grid\ItemInterface;
use MakinaCorpus\Layout\Render\GridRendererInterface;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout context edit form
 */
class LayoutItemOptionsForm extends FormBase
{
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
            $container->get('php_layout.grid_renderer')
        );
    }

    /**
     * Default constructor
     */
    public function __construct(GridRendererInterface $gridRenderer)
    {
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
    public function buildForm(array $form, FormStateInterface $formState, Context $context = null, EditToken $token = null, LayoutInterface $layout = null, int $itemId = 0)
    {
        if (!$context || !$token || !$layout || !$itemId) {
            return $form;
        }

        try {
            $item = $layout->findItem($itemId);
        } catch (GenericError $e) {
            return;
        }

        // @todo for now only nodes are supported
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
                '#submit' => [
                    function (array &$form, FormStateInterface $formState) use ($context, $token, $layout, $item) {
                        /** @var \MakinaCorpus\Layout\Grid\ItemInterface $item */
                        $item->setStyle($formState->getValue('style'));
                        $context->getTokenStorage()->update($token->getToken(), $layout);
                    },
                ],
            ],
            'cancel' => [
                '#type'   => 'submit',
                '#value'  => t("Cancel"),
                '#submit' => [function () {}],
            ],
        ];

        return $form;
    }
}
