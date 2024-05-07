<?php

namespace Drupal\role\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements a form to filter users by role.
 */
class RoleFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    $options = ['all' => $this->t('All')];
    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }

    $current_role = \Drupal::routeMatch()->getParameter('role');
    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Select role'),
      '#options' => $options,
      '#default_value' => $current_role ? $current_role : $form_state->getValue('role'),
    ];


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role = $form_state->getValue('role');
    $this->messenger()->addMessage($this->t('Selected role: @role', ['@role' => $role]));

    $url = Url::fromRoute('role.users', ['role' => $role]);
    $form_state->setRedirectUrl($url);

    $this->messenger()->addMessage($this->t('Filter applied for role @role', ['@role' => $role]));
  }

}
