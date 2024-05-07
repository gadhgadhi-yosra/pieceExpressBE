<?php declare(strict_types = 1);

namespace Drupal\otp_field;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for otp_field_processor plugins.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 1 2023
 */
abstract class OtpFieldProcessorPluginBase extends PluginBase implements OtpFieldProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function identityFieldType(): string {
    return 'textfield';
  }

  /**
   * {@inheritdoc}
   */
  public function identityPattern(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * TODO: Process pattern from ::identityPattern() here; and force children to call parent logic.
   */
  public function validateIdentity(string $identity): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  final public function sendSecretToIdentityOnce(string $identity, string $secret): bool {
    static $result = NULL;
    if (!is_null($result)) {
      return $result;
    }

    return $result = $this->sendSecretToIdentity($identity, $secret);
  }

}
