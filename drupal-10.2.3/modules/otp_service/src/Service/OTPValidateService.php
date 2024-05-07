<?php

namespace Drupal\otp_service\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserStorageInterface;
use PragmaRX\Google2FA\Google2FA;

/**
 * A service to validate user input codes.
 *
 * @package Drupal\otp_service\Services
 */
class OTPValidateService {

  /**
   * The current user.
   *
   * @var \Drupal\user\Entity\User
   */

  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */

  protected $userStorage;

  /**
   * OTPValidateService constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\user\UserStorageInterface $userStorage
   *   The user storage.
   */
  public function __construct(AccountInterface $currentUser, UserStorageInterface $userStorage) {
    $this->currentUser = $currentUser;
    $this->userStorage = $userStorage;
  }

  /**
   * Validates the OTP code returning TRUE or FALSE.
   */
  public function validateOtp($code) {
    $user = $this->userStorage->load($this->currentUser->id());
    // Get secret.
    $secret = $user->get('otp_secret')->getValue()[0]['value'];

    $google2fa = new Google2FA();
    // Validate code.
    if ($google2fa->verifyKey($secret, $code)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
