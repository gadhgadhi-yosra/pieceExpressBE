<?php

/**
 * API and examples.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 3 2023
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Example form class to demonstrate the usage of otp_field.
 */
class TestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_field_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['otp_example'] = [
      '#type' => 'otp_field',
      '#title' => 'OTP Field Example',
      '#required' => TRUE,
      '#default_value' => [
        // default otp_id
        'otp_id' => 'default_identity',
        // default otp_secret. not needed in most cases.
        'otp_secret' => 'default_secret',
      ],

      // @required: The ID of a OtpFieldProcessor plugin.
      '#otp_field_processor' => 'otp_field_sms_processor',

      // A site-unique key to distinguish the otp purpose. Defaults to the FORM_ID__Field_ID ($this->getFormId()).
      '#otp_key' => 'otp_example',

      // If set to TRUE, validation is skipped, and only the identity is shown as immutable.
      // The ['#default_value']['otp_id'] ket should be set.
      '#already_validated' => FALSE,

      // Configuration options
      '#otp_config' => [
        // Translated strings used in server-side.
        'strings' => [
          // titles and placeholders
          'identity_field_title'       => NULL, // Defaults to element's title.
          'identity_field_placeholder' => NULL, // Defaults to element's placeholder.
          'secret_field_title'         => t('OTP Secret', [], ['context' => 'otp_strings']),
          'secret_field_placeholder'   => '',

          // buttons
          'btn_send_code'       => t('Send Code', [], ['context' => 'otp_strings']),
          'btn_resend_code'     => t('Resend Code', [], ['context' => 'otp_strings']),
          'btn_change_identity' => t('Change identity', [], ['context' => 'otp_strings']),
          'btn_verify'          => t('Verify', [], ['context' => 'otp_strings']),
          'btn_verify_again'    => t('Verify again', [], ['context' => 'otp_strings']),

          // messages
          'msg_validated'          => t('Validated', [], ['context' => 'otp_strings']),
          'msg_identity_missing'   => t('Please enter your identity.', [], ['context' => 'otp_strings']),
          'msg_secret_missing'     => t('Please enter your secret.', [], ['context' => 'otp_strings']),
          'msg_identity_invalid'   => t('Identity is invalid.', [], ['context' => 'otp_strings']),
          'msg_secret_invalid'     => t('The secret does not match identity.', [], ['context' => 'otp_strings']),
          'msg_flood_detected'     => t('Too many tries detected. Please try again later.', [], ['context' => 'otp_strings']),
          'msg_secret_sent'        => t('New secret has been sent to you.', [], ['context' => 'otp_strings']),
          'msg_error_sending_code' => t('Could not send the secret code.', [], ['context' => 'otp_strings']),
        ],
      ],
    ];

    // This is a dummy field, to demonstrate form errors.
    $form['dummy_error'] = [
      '#type' => 'textfield',
      '#default_value' => 'ok',
      '#title' => 'Only "ok" is allowed'
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('dummy_error') != 'ok') {
      $form_state->setErrorByName('dummy_error', 'Only "ok" is allowed.');
    }

    /**
     * Note that even when the identity and secret are still not validated,
     * both of them will be available here.
     * So, if we need to do some work with them, we must first check for errors.
     */
    $otp_example = $form_state->getValue('otp_example');
    if (isset($form_state->getErrors()['otp_example'])) {
      // has errors.
      // however, the error will prevent submission.
      // but we should be aware of this situation.
      return;
    }
    $otp_id = $otp_example['otp_id'];
    $otp_secret = $otp_example['otp_secret'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $otp_example = $form_state->getValue('otp_example');
    // dpm($otp_example);

    /**
     * Once an OTP secret is used, we should expire it.
     * It's necessary for a true One-Time password.
     */
    $otp_id = $form_state->getValue('otp_example')['otp_id'];
    // #otp_key is always filled at this point.
    $otp_key = $form['otp_sms']['#otp_key'];
    \Drupal::service('otp_field.otp_provider')->secretUsed($otp_id, $otp_key);
  }

}
