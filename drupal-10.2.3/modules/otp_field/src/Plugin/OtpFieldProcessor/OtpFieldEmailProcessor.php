<?php declare(strict_types = 1);

namespace Drupal\otp_field\Plugin\OtpFieldProcessor;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\otp_field\OtpFieldProcessorPluginBase;
use Drupal\otp_field\OtpProvider;
use Drupal\otp_field\ValidationStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OTP Field: Email Processor
 *
 * @OtpFieldProcessor(
 *   id = "otp_field_email_processor",
 *   label = @Translation("OTP Field: Email Processor"),
 *   description = @Translation("Identity is an email address, and send the OTP secret via email.")
 * )
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 1 2023
 */
final class OtpFieldEmailProcessor extends OtpFieldProcessorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Flood control settings.
   * TODO: Make flood settings configurable.
   */
  protected const FLOOD_NAME = 'OtpFieldEmailProcessor';
  protected const FLOOD_WINDOW = 12 * 3600;
  protected const FLOOD_THRESHOLD = 100;

  /**
   * Constructor
   */
  public function __construct(array $configuration,
                                    $plugin_id,
                                    $plugin_definition,
                              protected FloodInterface $flood,
                              protected OtpProvider $otpProvider,
                              protected LoggerChannelInterface $logger,
                              protected MailManagerInterface $mailManager,
                              protected EmailValidatorInterface $emailValidator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flood'),
      $container->get('otp_field.otp_provider'),
      $container->get('logger.factory')->get('OtpFieldEmailProcessor'),
      $container->get('plugin.manager.mail'),
      $container->get('email.validator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function identityFieldType(): string {
    return 'email';
  }

  /**
   * {@inheritdoc}
   */
  public function validateIdentity(string $identity): bool {
    return $this->emailValidator->isValid($identity);
  }

  /**
   * {@inheritdoc}
   */
  public function validateSecret(string $identity, string $secret, string $otp_key = 'default'): array {

    /**
     * Flood control
     */
    if (!$this->flood->isAllowed(self::FLOOD_NAME, self::FLOOD_THRESHOLD, self::FLOOD_WINDOW)) {
      $this->logger->warning('Flood detected in OtpFieldEmailProcessor');
      return [
        'status' => ValidationStatus::ERROR,
        // '@strings:string_id' instructs the client to use a string found in drupalSettings.
        'message' => '@strings:msg_flood_detected',
      ];
    }

    /**
     * If $identity is empty, return error
     */
    if (empty($identity)) {
      return [
        'status' => ValidationStatus::ERROR,
        // '@strings:string_id' instructs the client to use a string found in drupalSettings.
        'message' => '@strings:msg_identity_missing',
      ];
    }

    /**
     * Validate identity
     */
    if (!$this->validateIdentity($identity)) {
      return [
        'status' => ValidationStatus::ERROR,
        // '@strings:string_id' instructs the client to use a string found in drupalSettings.
        'message' => '@strings:msg_identity_invalid',
      ];
    }

    // register flood event.
    $this->flood->register(self::FLOOD_NAME, self::FLOOD_WINDOW);

    /**
     * If only $identity is provided, generate new secret and send via email.
     */
    if (empty($secret)) {
      // TODO: allowed_chars and length should be configurable.
      $secret = $this->otpProvider->getSecret($identity, $otp_key, '123456789', 6);

      if ($this->sendSecretToIdentityOnce($identity, $secret)) {
        return [
          'status' => ValidationStatus::CODE_SENT,
          'message' => '@strings:msg_secret_sent',
        ];
      }
      else {
        return [
          'status' => ValidationStatus::ERROR,
          'message' => '@strings:msg_error_sending_code',
        ];
      }
    }
    else {
      if ($this->otpProvider->checkSecret($identity, $secret, $otp_key)) {
        return [
          'status' => ValidationStatus::SUCCESS,
          'message' => 'success',
        ];
      }
      else {
        return [
          'status' => ValidationStatus::INVALID,
          'message' => '@strings:msg_secret_invalid',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Send Email.
   * @param string $identity Email address.
   */
  public function sendSecretToIdentity(string $identity, string $secret): bool {
    // TODO: make email text configurable.
    $message = t('Your verification code: @code', [
      '@code' => $secret,
    ])->__toString();

    $this->mailManager->mail(
      module:   'otp_field',
      key:      'send_otp_secret',
      to:       $identity,
      langcode: \Drupal::currentUser()->getPreferredLangcode(),
      params:   ['message' => $message],
    );

    return TRUE;
  }

}
