<?php

namespace Drupal\otp_field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\otp_field\Form\SettingsForm;

/**
 * The otp_field.otp_provider service.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 2 2023
 */
class OtpProvider {

  /**
   * module config
   */
  protected ImmutableConfig $config;

  /**
   * Lifetime of newly generated secrets (per seconds).
   */
  protected readonly int $secret_lifetime;

  /**
   * When an existing secret is returned, its lifetime should not lower than this value.
   * Otherwise, it will be considered expired, and a new secret will be generated.
   * (Per second)
   */
  protected readonly int $secret_min_lifetime;

  /**
   * constructor.
   */
  public function __construct(
    protected Connection $db,
    protected ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $this->configFactory->get(SettingsForm::CONFIG_NAME);

    $this->secret_lifetime     = $this->config->get('otp_secret_lifetime');
    $this->secret_min_lifetime = $this->config->get('otp_secret_min_lifetime');
  }

  /**
   * Generate a new secret or get an existing secret.
   */
  public function getSecret(string $identity, string $otp_key = 'default',
                            string $allowed_chars = '123456789', int $length = 6): string {

    $time = \Drupal::time()->getRequestTime();

    // retrieve existing secret (if available)
    $secret_arr = $this->db->select('otp_field_secrets', 's')
      ->fields('s')
      ->condition('otp_key', $otp_key)
      ->condition('identity', $identity)
      ->execute()
      ->fetchAssoc();

    // check if it is not expired.
    if (!empty($secret_arr) && ($secret_arr['expire'] >= $time + $this->secret_min_lifetime)) {
      return $secret_arr['secret'];
    }
    else {
      // generate a new secret
      $secret = $this->generateSecretString($allowed_chars, $length, $otp_key, $identity);

      // delete existing record
      if (!empty($secret_arr)) {
        $this->db->delete('otp_field_secrets')
          ->condition('otp_key', $otp_key)
          ->condition('identity', $identity)
          ->execute();
      }

      // save to the database
      $this->db->insert('otp_field_secrets')
        ->fields([
          'otp_key' => $otp_key,
          'identity' => $identity,
          'secret' => $secret,
          'created' => $time,
          'expire' => $time + $this->secret_lifetime,
        ])
        ->execute();

      return $secret;
    }
  }

  /**
   * Check the validity of an OTP secret.
   */
  public function checkSecret(string $identity, string $secret, string $otp_key = 'default'): bool {
    $time = \Drupal::time()->getRequestTime();

    $record = $this->db->select('otp_field_secrets', 's')
      ->fields('s')
      ->condition('otp_key', $otp_key)
      ->condition('identity', $identity)
      ->condition('secret', $secret)
      ->condition('expire', $time, '>')
      ->execute()
      ->fetchAssoc();

    return !empty($record);
  }

  /**
   * Delete a secret.
   *
   * When a secret is used, call this function to delete it.
   * A single secret must not be used multiple times (for security).
   *
   * @param string $identity The OTP identity.
   * @param string $otp_key The OTP key.
   *   This argument is required because the default value of otp_key depends on the form and field name.
   *   And we should force the developer to pass in the correct value.
   *
   *   Generally, we should call it this way in FormClass::submitForm():
   *   \Drupal::service('otp_field.otp_provider')
   *     ->secretUsed($identity, $form['otp_field_name']['#otp_key']);
   */
  public function secretUsed(string $identity, string $otp_key): void {
    $this->db->delete('otp_field_secrets')
      ->condition('otp_key', $otp_key)
      ->condition('identity', $identity)
      ->execute();
  }

  /**
   * Generate new unique secret string.
   */
  protected function generateSecretString(string $allowed_chars, int $length, string $otp_key, string $identity): string {
    do {
      $string = '';
      for ($i = 1; $i <= $length; $i++) {
        $r = random_int(0, strlen($allowed_chars) - 1);
        $string .= $allowed_chars[$r];
      }

      // check if $string is unique
      $exists = $this->db->select('otp_field_secrets', 's')
        ->fields('s')
        ->condition('otp_key', $otp_key)
        ->condition('identity', $identity)
        ->condition('secret', $string)
        ->execute()
        ->fetchAssoc();

    } while(!empty($exists));

    return $string;
  }
}
