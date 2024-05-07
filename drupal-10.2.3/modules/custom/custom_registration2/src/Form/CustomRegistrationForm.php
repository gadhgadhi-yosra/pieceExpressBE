<?php

namespace Drupal\custom_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides a custom registration form.
 */
class CustomRegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['nom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom'),
      '#required' => TRUE,
    ];

    $form['prenom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prénom'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Adresse email'),
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Mot de passe'),
      '#required' => TRUE,
    ];

    // Modifié pour ajouter un deuxième champ de mot de passe pour la confirmation
    $form['password_confirm'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirmer le mot de passe'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('S\'inscrire'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('password') !== $form_state->getValue('password_confirm')) {
      $form_state->setErrorByName('password_confirm', $this->t('Les mots de passe ne correspondent pas.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = User::create([
      'name' => $form_state->getValue('email'),
      'mail' => $form_state->getValue('email'),
      'pass' => $form_state->getValue('password'),
      'field_nom' => $form_state->getValue('nom'),
      'field_prenom' => $form_state->getValue('prenom'),
    ]);

    $user->save();

    $this->messenger()->addMessage($this->t('Inscription réussie.'));
  }
}
