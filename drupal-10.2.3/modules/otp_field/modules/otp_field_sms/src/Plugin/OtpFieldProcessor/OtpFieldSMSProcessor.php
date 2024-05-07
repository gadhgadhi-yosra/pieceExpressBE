<?php declare(strict_types = 1);

namespace Drupal\otp_field_sms\Plugin\OtpFieldProcessor;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\otp_field\Element\OtpField;
use Drupal\otp_field\OtpFieldProcessorPluginBase;
use Drupal\otp_field\OtpProvider;
use Drupal\otp_field\ValidationStatus;
use Drupal\sms\Direction;
use Drupal\sms\Exception\RecipientRouteException;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OTP Field: SMS Processor
 *
 * @OtpFieldProcessor(
 *   id = "otp_field_sms_processor",
 *   label = @Translation("OTP Field: SMS Processor"),
 *   description = @Translation("Identity is a mobile number, and send the OTP secret via sms.")
 * )
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 1 2023
 */
final class OtpFieldSMSProcessor extends OtpFieldProcessorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Flood control settings.
   * TODO: Make flood settings configurable.
   */
  protected const FLOOD_NAME = 'OtpFieldSMSProcessor';
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
                              protected SmsProviderInterface $sms,
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
      $container->get('logger.factory')->get('OtpFieldSMSProcessor'),
      $container->get('sms.provider'),
    );
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
    return '09[0-9]{9}';
  }

  /**
   * {@inheritdoc}
   */
  public function validateIdentity(string $identity): bool {
    return (bool)preg_match('/^09[0-9]{9}$/', $identity);
  }

  /**
   * {@inheritdoc}
   */
  public function validateSecret(string $identity, string $secret, string $otp_key = 'default'): array {

    /**
     * Flood control
     */
    if (!$this->flood->isAllowed(self::FLOOD_NAME, self::FLOOD_THRESHOLD, self::FLOOD_WINDOW)) {
      $this->logger->warning('Flood detected in OtpFieldSMSProcessor');
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
     * If only $identity is provided, generate new secret and send via SMS.
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
   * Send SMS using 'smsframework' module.
   */
  public function sendSecretToIdentity(string $identity, string $secret): bool {
    $sms = new SmsMessage();
    $sms->setMessage($secret); // TODO: make sms text configurable.
    $sms->addRecipient($identity);
    $sms->setDirection(Direction::OUTGOING);

    try {
      $sent = $this->sms->send($sms);

      return !empty($sent);
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception in sendSMS: @msg', ['@msg' => $e->getMessage()]);

      return FALSE;
    }
  }

}
