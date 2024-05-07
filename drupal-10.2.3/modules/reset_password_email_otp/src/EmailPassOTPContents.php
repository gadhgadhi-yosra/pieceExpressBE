<?php

namespace Drupal\reset_password_email_otp;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Service that handles wordcloud data.
 */
class EmailPassOTPContents {
  use StringTranslationTrait;
  /**
   * Database Connection Service.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $dbConn;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * User Account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Messenger\Messenger $messenger_status
   *   Messenger.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account Interface.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config Factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time_service
   *   The datetime.time service.
   */
  public function __construct(Connection $connection, Messenger $messenger_status, LoggerChannelFactoryInterface $logger_factory, AccountInterface $account, ConfigFactory $configFactory, MailManagerInterface $mail_manager, TimeInterface $time_service) {
    $this->dbConn = $connection;
    $this->messenger = $messenger_status;
    $this->loggerFactory = $logger_factory->get('reset_password_email_otp');
    $this->account = $account;
    $this->configFactory = $configFactory;
    $this->mailManager = $mail_manager;
    $this->timeService = $time_service;
  }

  /**
   * Get reset OTP.
   *
   * @param string $length
   *   String limit OTP.
   *
   * @return string
   *   OTP return.
   */
  public function getOtpForResetPassword($length) {
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet .= "0123456789";
    $codeAlphabet .= "!@#$%^&";
    $max = strlen($codeAlphabet);

    for ($i = 0; $i < $length; $i++) {
      $token .= $codeAlphabet[random_int(0, $max - 1)];
    }

    return $token;
  }

  /**
   * Save sent OTP on db.
   *
   * @param int $uid
   *   User uid.
   * @param int $otp
   *   Random OTP.
   */
  public function sendUserResetPasswordOtpWithEmail($uid, $otp) {
    $data = [
      'uid' => $uid,
      'OTP' => $otp,
      'count' => 0,
      'time' => $this->timeService->getRequestTime(),
    ];
    $this->dbConn->insert('reset_password_email_otp_list')
      ->fields($data)
      ->execute();

    return TRUE;
  }

  /**
   * Send mail callback.
   */
  public function resetPasswordEmailOtpSend($mail, $otp, $key_mail) {
    $module = 'reset_password_email_otp';
    switch ($key_mail) {
      case 'reset-password-email-otp':
        // Get Admin email id.
        $params['message'] = str_replace('[OTP]', $otp, $this->configFactory->getEditable('reset_password_email_otp.settings')
          ->get('reset_password_email_otp_mail_body'));
        $langcode = $this->account->getPreferredLangcode();
        $send = TRUE;
        // Send mail to all user.
        $to = $mail;
        if (!empty($to)) {
          $result = $this->mailManager->mail($module, $key_mail, $to, $langcode, $params, NULL, $send);
        }
        if ($result['result'] != TRUE) {
          $message = $this->t('There was a problem sending your email notification to @email.', ['@email' => 'users']);
          $this->messenger->addError($message);
          $this->loggerFactory->error($message);

          return;
        }
        break;

      default;
    }
    $message = $this->t('An email notification with OTP has been sent to @email', ['@email' => 'users']);
    $this->loggerFactory->notice($message);
  }

}
