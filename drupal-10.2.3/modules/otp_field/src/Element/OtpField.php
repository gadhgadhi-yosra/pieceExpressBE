<?php

namespace Drupal\otp_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Annotation\FormElement;
use Drupal\Core\Render\Element\FormElement as FormElementBase;
use Drupal\otp_field\Form\SettingsForm;
use Drupal\otp_field\OtpFieldProcessorPluginBase;
use Drupal\otp_field\OtpFieldProcessorPluginManager;
use Drupal\otp_field\ValidationStatus;

/**
 * Provides the otp_field element.
 *
 * @FormElement("otp_field")
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 1 2023
 */
class OtpField extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#theme_wrappers' => ['form_element'],
      '#process' => [
        [$class, 'processOtpField'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    /**
     * If not submitted yet, provide default value.
     */
    if ($input === FALSE) {
      $element += ['#default_value' => []];

      return $element['#default_value'] + ['otp_id' => '', 'otp_secret' => ''];
    }

    /**
     * If element['#already_validated'] is set, we should ignore the submitted value
     * and always return the default value.
     * (For security reasons)
     */
    if ($element['#already_validated'] ?? FALSE) {
      return [
        'otp_id'     => $element['#default_value']['otp_id'] ?? '',
        'otp_secret' => '',
      ];
    }

    $value = ['otp_id' => '', 'otp_secret' => ''];
    // Throw out all invalid array keys; we only allow otp_id and otp_secret.
    foreach ($value as $allowed_key => $default) {
      // These should be strings, but allow other scalars since they might be
      // a valid input in programmatic form submissions.
      // Any nested array values are ignored.
      if (isset($input['otp_container'][$allowed_key]) && is_scalar($input['otp_container'][$allowed_key])) {
        $value[$allowed_key] = (string) $input['otp_container'][$allowed_key];
      }
    }

    return $value;
  }

  /**
   * Process the otp_field.
   * (#process)
   */
  public static function processOtpField(&$element, FormStateInterface $form_state, &$complete_form) {
    /**
     * initialize otp processor plugin
     */
    /** @var OtpFieldProcessorPluginManager $pm */
    $pm = \Drupal::service('plugin.manager.otp_field_processor');
    $otp_processor_id = $element['#otp_field_processor'] ?? NULL;
    if (is_null($otp_processor_id)) {
      throw new \Exception('#otp_field_processor is mandatory.');
    }
    /** @var OtpFieldProcessorPluginBase $otp_processor */
    $otp_processor = $pm->createInstance($otp_processor_id);

    /**
     * Read submitted values if available.
     */
    $otp_id     = $element['#value']['otp_id'] ?? NULL;
    $otp_secret = $element['#value']['otp_secret'] ?? NULL;

    /**
     * default values
     */
    $defaults = [
      // default otp_key is constructed as FORM_ID__Field_ID
      '#otp_key' => $form_state->getFormObject()->getFormId() . '__' . $element['#name'],
      '#already_validated' => FALSE,
      '#otp_config' => [
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
    $element = array_replace_recursive($defaults, $element);;

    /**
     * validate and send the result for Javascript usage
     */
    if ($element['#already_validated']) {
      $validation_result = ['status' => ValidationStatus::SUCCESS];
    }
    elseif (!empty($otp_id)) {
      $validation_result = $otp_processor->validateSecret($otp_id, $otp_secret, $element['#otp_key']);

      // replace otp strings
      $validation_result['message'] = static::replaceOtpStrings($validation_result['message'], $element);
    }
    else {
      // when $otp_id is empty, no data is submitted. so no validation happens.
      $validation_result = ['status' => ValidationStatus::NONE];
    }
    $element['#attached']['drupalSettings']['otp_field'][$element['#otp_key']]['validation_result'] = $validation_result;

    /**
     * Send strings for js usage.
     */
    $element['#attached']['drupalSettings']['otp_field'][$element['#otp_key']]['strings'] = $element['#otp_config']['strings'];

    /**
     * Send other js settings
     */
    $config = \Drupal::config(SettingsForm::CONFIG_NAME);
    $element['#attached']['drupalSettings']['otp_field']['settings'] = [
      'countdown_seconds' => $config->get('countdown_seconds'),
    ];

    /**
     * Send OTP Plugin ID and OTP Key to javascript,
     * so that we can access them later in the Controller.
     */
    $element['#attached']['drupalSettings']['otp_field'][$element['#otp_key']]['otp_plugin'] = $element['#otp_field_processor'];
    $element['#attached']['drupalSettings']['otp_field'][$element['#otp_key']]['otp_key']    = $element['#otp_key'];


    // force input names to be like this field_name[otp_id] and field_name[otp_secret] instead of plain 'otp_id' and 'otp_secret'
    $element['#tree'] = TRUE;

    // We should add a container so that we can access the whole field in js reliably.
    $element['otp_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['otp-field-container'],
        'data-otp-key' => $element['#otp_key'],
      ],
    ];

    // Move element title to the otp_id field.
    $element_title = $element['#title'] ?? '';
    $element['#title'] = '';

    $element['otp_container']['otp_id'] = [
      '#type' => $otp_processor->identityFieldType(),
      '#title' => $element['#otp_config']['strings']['identity_field_title'] ?? $element_title,
      '#placeholder' => $element['#otp_config']['strings']['identity_field_placeholder'] ?? $element['#placeholder'] ?? '',
      '#value' => $otp_id,
      '#required' => $element['#required'],
      // Although I set #required, drupal core still does not validate it. Unless I also set #needs_validation.
      '#needs_validation' => TRUE,
      '#attributes' => [
        'class' => ['otp-field-otp-id', 'js-otp-field-otp-id'],
      ],
    ];

    /**
     * Copy #states. Note that we only add #states to otp_id and the container. (Not the otp_secret)
     */
    if (!empty($element['#states'])) {
      $element['otp_container']['#states']           = $element['#states'];
      $element['otp_container']['otp_id']['#states'] = $element['#states'];

      /**
       * Do not add visible or hidden state to otp_id.
       * Because that will conflict with the logic inside the otp_field_behavior.js file.
       * The otp_id field will be shown/hidden by our own logic.
       */
      unset($element['otp_container']['otp_id']['#states']['visible']);
      unset($element['otp_container']['otp_id']['#states']['hidden']);
    }

    // If identity_pattern is used.
    $identity_pattern = $otp_processor->identityPattern();
    if ($identity_pattern) {
      $element['otp_container']['otp_id']['#pattern'] = $identity_pattern;
    }

    // If already validated, disable the field and apply proper style.
    if ($element['#already_validated']) {
      $element['otp_container']['otp_id']['#attributes']['readonly'] = 'readonly';
    }

    $element['otp_container']['otp_secret'] = [
      '#type' => 'textfield',
      '#title' => $element['#otp_config']['strings']['secret_field_title'],
      '#placeholder' => $element['#otp_config']['strings']['secret_field_placeholder'],
      '#value' => $otp_secret,
      '#required' => !empty($element['#value']['otp_id']) &&
                     $otp_processor->validateIdentity($element['#value']['otp_id']),
      // Although I set #required, drupal core still does not validate it. Unless I also set #needs_validation.
      // TODO: #needs_validation on otp_secret can show redundant error...
      // TODO: ...Because we validate it again in validateOtpField(). Double check it.
      // '#needs_validation' => TRUE,
      '#attributes' => [
        'class' => ['otp-field-otp-id', 'js-otp-field-otp-secret'],
      ],
      // If already validated, hide the secret field.
      '#access' => !$element['#already_validated'],
    ];
    $element['#element_validate'] = [[static::class, 'validateOtpField']];

    // attach libraries
    $element['#attached']['library'][] = 'otp_field/otp_field_behavior';
    $element['#attached']['library'][] = 'otp_field/otp_field_theme';

    return $element;
  }

  /**
   * Validates the otp_field.
   * (#element_validate)
   */
  public static function validateOtpField(&$element, FormStateInterface $form_state, &$complete_form) {
    /**
     * Read submitted values.
     */
    $otp_id     = trim($element['otp_container']['otp_id']['#value']);
    $otp_secret = trim($element['otp_container']['otp_secret']['#value']);

    /**
     * If #required is not set, and otp_id and otp_secret are left empty, no validation needed.
     * Do not force user to fill an optional field.
     */
    $required = $element['#required'] ?? FALSE;
    if (!$required && empty($otp_id) && empty($otp_secret)) {
      return $element;
    }

    /**
     * If #required is set, check if otp_id is filled.
     *
     * Even when #required is set and otp_id is empty, this function is always called.
     * So we should check it here, so that if it's left empty, we do not proceed with validation.
     *
     * Note: we don't need to set error on the field.
     * Because drupal will show the error, since I have set
     * ['#needs_validation'] => TRUE on the otp_id field.
     */
    if ($required && empty($otp_id)) {
      // stop validation.
      return $element;
    }

    /**
     * If #already_validated, no validation is needed.
     */
    if ($element['#already_validated']) {
      return $element;
    }

    /**
     * initialize otp processor plugin
     */
    /** @var OtpFieldProcessorPluginManager $pm */
    $pm = \Drupal::service('plugin.manager.otp_field_processor');
    $otp_processor_id = $element['#otp_field_processor'] ?? NULL;
    if (is_null($otp_processor_id)) {
      throw new \Exception('#otp_field_processor is mandatory.');
    }
    /** @var OtpFieldProcessorPluginBase $otp_processor */
    $otp_processor = $pm->createInstance($otp_processor_id);

    // Validate identity
    if (!$otp_processor->validateIdentity($otp_id)) {
      $form_state->setError($element, $element['#otp_config']['strings']['msg_identity_invalid']);
    }

    /**
     * When $otp_id is present, $otp_secret must also be present to proceed.
     *
     * TODO: If I set ['#needs_validation'] => TRUE on the otp_secret field, drupal will show proper...
     * TODO: ...error on the field. So I may remove this section here. Double check it.
     * TODO: ...Currently I have removed #needs_validation for the otp_secret. But I may change that.
     */
    if (!empty($otp_id) && empty($otp_secret)) {
      $form_state->setError($element, $element['#otp_config']['strings']['msg_secret_missing']);
    }

    // validate the secret using plugin.
    ['status' => $status, 'message' => $message] = $otp_processor->validateSecret($otp_id, $otp_secret, $element['#otp_key']);

    // Replace otp strings
    $message = static::replaceOtpStrings($message, $element);

    switch ($status) {
      case ValidationStatus::SUCCESS:
        break;
      case ValidationStatus::CODE_SENT:
      case ValidationStatus::INVALID:
      case ValidationStatus::ERROR:
        $form_state->setError($element, $message);
        break;
    }

    return $element;
  }

  /**
   *
   */
  public static function replaceOtpStrings(string $string, array $element): string {
    $strings_pattern = '/^@strings:([a-z_]+)$/';
    if (preg_match($strings_pattern, $string, $matches)) {
      $string_id = $matches[1];

      if (isset($element['#otp_config']['strings'][$string_id])) {
        return $element['#otp_config']['strings'][$string_id]->__toString();
      }
    }

    return $string;
  }

}
