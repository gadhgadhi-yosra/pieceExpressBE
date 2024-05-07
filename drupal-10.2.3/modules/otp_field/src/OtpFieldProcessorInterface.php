<?php declare(strict_types = 1);

namespace Drupal\otp_field;

/**
 * Interface for otp_field_processor plugins.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 1 2023
 */
interface OtpFieldProcessorInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Return a Form Api field type for identity field.
   *
   * Example: 'textfield' or 'email'
   */
  public function identityFieldType(): string;

  /**
   * Return a Form Api regex pattern for validating the identity field.
   * Optional.
   */
  public function identityPattern(): ?string;

  /**
   * Validate the identity string.
   *
   * NOTE: When identityPattern() is used, we should always duplicate its logic in validateIdentity()
   * Because the user may send fake ajax request, and bypass Form Api validation.
   *
   * TODO: @see OtpFieldProcessorPluginBase::validateIdentity()
   */
  public function validateIdentity(string $identity): bool;

  /**
   * Validate OTP secret.
   *
   * @param string $identity
   *   The identity of the user.
   *   Typically, it may be a mobile number or an email address.
   * @param string $secret
   *   The secret to validate.
   *   If empty, a new secret may be generated and sent to the user.
   * @param string $otp_key
   *   A site-unique key.
   *   If the same identity is used in multiple places in a site, we can generate
   *   separate secrets for each of them.
   *
   * @return array(
   *    'status'  => \Drupal\otp_field\ValidationStatus, // validation status
   *    'message' => string, // the message to be shown to the user.
   *  )
   * The returned message may contain @strings:string_id
   * That will be replaced by an otp_string, as defined in field properties.
   */
  public function validateSecret(string $identity, string $secret, string $otp_key = 'default'): array;

  /**
   * Send the secret to identity, prevent duplicate call.
   *
   * This method should be called instead of sendSecretToIdentity() in plugin classes.
   *
   * Important: prevent duplicate messages in single request.
   *  This function can be called multiple times within the same request.
   *  And we should avoid sending duplicate messages.
   *  Reason:
   * @see \Drupal\otp_field\Element\OtpField::processOtpField() calls $otp_processor->validateSecret()
   * @see \Drupal\otp_field\Element\OtpField::validateOtpField() also calls $otp_processor->validateSecret()
   *    when the user has entered the identity, and submits the form, both of the above functions are called
   *    in single request.
   *    Both calls are required and cannot be avoided. I don't have any better idea at the moment.
   *    So, we should handle that here.
   */
  public function sendSecretToIdentityOnce(string $identity, string $secret): bool;

  /**
   * Send the secret to identity actual logic.
   *
   * This method should be implemented in plugin class, but should not be called.
   * Instead of this, call $this->sendSecretToIdentityOnce().
   */
  public function sendSecretToIdentity(string $identity, string $secret): bool;

}
