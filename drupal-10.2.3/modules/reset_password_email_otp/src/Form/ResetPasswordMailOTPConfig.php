<?php

namespace Drupal\reset_password_email_otp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure reset password email otp settings.
 */
class ResetPasswordMailOTPConfig extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'reset_password_email_otp.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reset_password_email_otp_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['reset_password_email_otp_wrong_attempt'] = [
      '#title' => $this->t('Wrong attempt OTP limit'),
      '#type' => 'textfield',
      '#description' => $this->t('How many time user can attempt wrong OTP.'),
      '#default_value' => $config->get('reset_password_email_otp_wrong_attempt'),
    ];

    $form['reset_password_email_otp_length'] = [
      '#title' => $this->t('Generate OTP character length'),
      '#type' => 'textfield',
      '#description' => $this->t('Generate OTP character length.'),
      '#default_value' => $config->get('reset_password_email_otp_length'),
    ];

    $form['reset_password_email_otp_mail_subject'] = [
      '#title' => $this->t('Reset Mail OTP mail subject'),
      '#type' => 'textfield',
      '#description' => $this->t('Reset Password Mail OTP mail subject content.'),
      '#default_value' => $config->get('reset_password_email_otp_mail_subject'),
    ];

    $form['reset_password_email_otp_mail_body'] = [
      '#title' => $this->t('Reset Password Mail OTP mail body'),
      '#type' => 'textarea',
      '#description' => $this->t('Make sure to use [OTP] in email body for contains OTP value.'),
      '#default_value' => $config->get('reset_password_email_otp_mail_body'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $otp = $form_state->getValue('reset_password_email_otp_mail_body');
    $pattern = "/\[OTP\]/";
    if (!preg_match($pattern, $otp)) {
      $form_state->setErrorByName('reset_password_email_otp_mail_body', $this->t('Please add [OTP] text in Email body.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('reset_password_email_otp.settings');
    // Save form values.
    $form_values = $form_state->getValues();
    foreach ($form_values as $key => $value) {
      $config->set($key, $form_state->getValue($key))->save();
    }

    parent::submitForm($form, $form_state);
  }

}
