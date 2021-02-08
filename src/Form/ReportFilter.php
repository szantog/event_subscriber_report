<?php

namespace Drupal\event_subscriber_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Event Subscriber Report form.
 */
class ReportFilter extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_subscriber_report_report_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['module'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter by module'),
      '#size' => 20,
      '#default_value' => $this->getRequest()->get('module'),
      '#attributes' => [
        'style' => 'margin-right: 20px'
      ]
    ];

    $form['event'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter by event'),
      '#size' => 20,
      '#default_value' => $this->getRequest()->get('event'),
      '#attributes' => [
        'style' => 'margin-right: 20px'
      ]
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'style' => 'margin-top: 32px'
      ]
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $form['#attributes'] = [
      'style' => 'display: flex',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getRouteMatch()->getRouteName(), [
      'module' => $form_state->getValue('module'),
      'event' => $form_state->getValue('event'),
    ]);
  }

}
