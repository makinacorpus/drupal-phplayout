<?php

namespace MakinaCorpus\Drupal\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Layout\Controller\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout context edit form
 */
class LayoutContextEditForm extends FormBase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('php_layout.context'));
    }

    /**
     * Default constructor
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'php_layout_context_edit_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        if ($this->context->isEmpty()) {
            return;
        }

        $form['actions']['#type'] = 'actions';

        // Please note that access checks on layouts are being done when they
        // are added to the context, on the add() method, the $isEditable
        // boolean drives this.
        if ($this->context->hasToken()) {
            $form['actions']['save_page'] = [
                '#type'   => 'submit',
                '#value'  => $this->t("Save composition"),
                '#submit' => ['::saveSubmit']
            ];
            $form['actions']['cancel_page'] = [
                '#type'   => 'submit',
                '#value'  => $this->t("Cancel"),
                '#submit' => ['::cancelSubmit']
            ];
        } else {
            $form['actions']['edit'] = [
                '#type'   => 'submit',
                '#value'  => $this->t("Edit composition"),
                '#submit' => ['::editSubmit']
            ];
        }

        return $form;
    }

    /**
     * Save form submit
     */
    public function saveSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->context->hasToken()) {
            $this->context->commit();

            drupal_set_message($this->t("Changed have been saved"));

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', PHP_LAYOUT_TOKEN_PARAMETER])]
            );
        }
    }

    /**
     * Cancel form submit
     */
    public function cancelSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->context->hasToken()) {
            $this->context->rollback();

            drupal_set_message($this->t("Changes have been dropped"), 'error');

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', PHP_LAYOUT_TOKEN_PARAMETER])]
            );
        }
    }

    /**
     * Edit form submit
     */
    public function editSubmit(array &$form, FormStateInterface $form_state)
    {
        if (!$this->context->hasToken()) {
            $token = $this->context->createEditToken();

            $form_state->setRedirect(
                current_path(),
                ['query' => [PHP_LAYOUT_TOKEN_PARAMETER => $token->getToken()] + drupal_get_query_parameters()]
            );
        }
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Nothing to do.
    }
}
