<?php

namespace Drupal\otp_field_user_auth;

use Drupal\otp_field_user_auth\Form\SettingsForm;
use Drupal\user\Entity\User;

/**
 * Common utils for otp_field_user_auth
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 5 2023
 */
trait OtpUserAuthUtilsTrait {

  /**
   * Register new user by mobile or email address.
   *
   * @param string $by Either 'mobile' or 'email'.
   * @param string $mobile_or_email The mobile number or email address as defined in $by.
   *
   * @return ?int New user ID or null on failure.
   */
  public function registerNewUserBy(string $by, string $mobile_or_email): ?int {

    // make sure $by is valid.
    if (!in_array($by, ['mobile', 'email'])) {
      return NULL;
    }

    $config = \Drupal::config(SettingsForm::CONFIG_NAME);

    $new_user = User::create();

    // generate random password
    $password = $this->generateRandomPassword();
    $new_user->setPassword($password);

    if ($by == 'mobile') {
      // set phone field.
      $phone_field = $config->get('phone_number');
      $new_user->set($phone_field, $mobile_or_email);

      // generate email address
      $email = $this->generateUniqueEmailFromMobileNumber($mobile_or_email);
    }
    else {
      $email = $mobile_or_email;
    }
    $new_user->setEmail($email);
    $new_user->set('init', $email);

    $new_user->setUsername($mobile_or_email);
    $new_user->enforceIsNew();

    // set language.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $new_user->set('langcode', $language);
    $new_user->set('preferred_langcode', $language);
    $new_user->set('preferred_admin_langcode', $language);

    // assign a role if configured in settings
    $new_user_role = $config->get('new_user_role');
    if ($new_user_role) {
      $new_user->addRole($new_user_role);
    }

    // set user to be active
    $new_user->activate();

    // Save user account.
    try {
      $new_user->save();

      /**
       * the phone field may be managed by smsframework.
       * smsframework, expires and deletes the phone number.
       */
      /*
      if ($by == 'mobile') {
        $sms_phone_number_verifier = \Drupal::service('sms.phone_number.verification');
        $sms_v = $sms_phone_number_verifier->getPhoneNumberSettingsForEntity($new_user);
        $sms_v->setStatus(TRUE)
          ->setCode('')
          ->save();
      }
      */

      return $new_user->id();
    }
    catch (\Throwable $e) {
      \Drupal::logger('otp_field_user_auth')->error('Exception in registerNewUserBy: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      return NULL;
    }
  }

  /**
   * Generate random password.
   */
  public function generateRandomPassword(): string {
    $bytes = 15;
    return bin2hex(random_bytes($bytes));
  }

  /**
   * Generate unique email address from mobile number.
   */
  public function generateUniqueEmailFromMobileNumber(string $mobile): string {
    // remove non-numerics from mobile number
    $mobile = preg_replace('/[^0-9]/', '', $mobile);

    $counter = 0;
    do {
      // build email address.
      $email = $mobile . '.otp_field';
      if ($counter > 0) {
        $email .= '.' . $counter;
      }
      // Use @127.0.0.1 because it's a fake email address and if we use invalid domain name,
      // it may cause delays on DNS resolution whenever trying to send email to the user.
      // Note that Drupal considers @localhost to be invalid.
      $email .= '@127.0.0.1';

      // Check if $email is unique
      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $email]);
      $success = empty($users);

      // increase counter.
      $counter++;

    } while(!$success);

    return $email;
  }

  /**
   * Check if a user can login using OTP.
   */
  public function checkUserCanLoginUsingOTP(User $user): bool {
    $permission = 'login by otp_field';

    if ($user->isBlocked()) {
      // user is blocked.
      return FALSE;
    }

    $config = \Drupal::config(SettingsForm::CONFIG_NAME);

    /**
     * Check uid=1
     */
    $prevent_uid1_login = $config->get('prevent_uid1_login');
    if ($prevent_uid1_login && ($user->id() == 1)) {
      // user is uid=1
      return FALSE;
    }

    /**
     * Check administrator role
     */
    $prevent_admin_role_login = $config->get('prevent_admin_role_login');
    if ($prevent_admin_role_login) {
      foreach ($user->getRoles() as $role_id) {
        if (\Drupal::entityTypeManager()->getStorage('user_role')
              ->load($role_id)->isAdmin()) {
          // user has administrator role.
          return FALSE;
        }
      }
    }

    // check by permissions.
    return $user->hasPermission($permission);
  }

  /**
   * Login user
   */
  public function loginUser(int $uid): void {
    $account = User::load($uid);
    user_login_finalize($account);
  }

}
