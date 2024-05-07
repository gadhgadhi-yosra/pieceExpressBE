<?php

namespace Drupal\otp_field_user_auth\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\otp_field\OtpProvider;
use Drupal\otp_field_user_auth\OtpUserAuthUtilsTrait;
use Drupal\user\Entity\User;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * User authentication form.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 2 2023
 */
class UserAuthForm extends FormBase {

  use OtpUserAuthUtilsTrait;

  /**
   * Constructor.
   */
  public function __construct(
    protected ImmutableConfig $config,
    protected UserStorageInterface $userStorage,
    protected RoleStorageInterface $roleStorage,
    protected OtpProvider $otpProvider,
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get(SettingsForm::CONFIG_NAME),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('otp_field.otp_provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_field_user_auth_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /**
     * Determine which processor plugins are selected
     */
    // use array_filter to remove unchecked plugins (value=0)
    $enabled_processor_plugins = array_filter($this->config->get('enabled_processor_plugins'));

    if (empty($enabled_processor_plugins)) {
      $this->messenger()->addError($this->t('No OTP plugins are enabled.'));

      throw new AccessDeniedHttpException('No OTP plugins are enabled.');
    }

    // check if multiple plugins are enabled.
    $multiple_methods = count($enabled_processor_plugins) > 1;

    /**
     * Create Form API arrays for enabled plugins.
     */
    $otp_sms = $otp_email = NULL;
    if (isset($enabled_processor_plugins['otp_field_sms_processor'])) {

      /**
       * Determine the phone_number field.
       * This is required for sending sms messages.
       */
      $phone_field = $this->config->get('phone_number');
      if (empty($phone_field)) {
        $this->messenger()->addError($this->t('Phone number field is not configured. Please contact site administrator.'));
        throw new AccessDeniedHttpException('Phone number field is not configured.');
      }

      $otp_sms = [
        '#type' => 'otp_field',
        '#title' => $this->t('Mobile number'),
        '#otp_field_processor' => 'otp_field_sms_processor',
        '#placeholder' => $this->t('e.g. 09391234567'),
        '#otp_config' => [
          'strings' => [
            'secret_field_title'      => $this->t('Your secret code'),
            'msg_secret_sent'         => $this->t('New secret has been sent to your mobile number.'),
            'msg_error_sending_code'  => $this->t('Error sending SMS message.'),
          ],
        ],
      ];
    }
    if (isset($enabled_processor_plugins['otp_field_email_processor'])) {
      $otp_email = [
        '#type' => 'otp_field',
        '#title' => $this->t('Email address'),
        '#otp_field_processor' => 'otp_field_email_processor',
        '#placeholder' => $this->t('e.g. you@gmail.com'),
        '#otp_config' => [
          'strings' => [
            'secret_field_title'      => $this->t('Your secret code'),
            'msg_secret_sent'         => $this->t('New secret has been sent to your email address.'),
            'msg_error_sending_code'  => $this->t('Error sending E-mail.'),
            'msg_secret_invalid'      => $this->t('The secret is invalid or expired. Please try again.'),
          ],
        ],
      ];
    }

    if ($multiple_methods) {
      $form_state->set('multiple_methods', true);
      $form['otp_method'] = [
        '#type' => 'radios',
        '#title' => $this->t('OTP method'),
        '#description' => $this->t('Please choose how you want to receive your OTP.'),
        '#options' => [
          'sms' => 'Send to my mobile (SMS).',
          'email' => 'Send to my e-mail address.',
        ],
        '#default_value' => 'sms',
        '#required' => TRUE,
      ];
      $form['otp_sms'] = $otp_sms + [
          '#states' => [
            'required' => [
              ':input[name=otp_method]' => ['value' => 'sms'],
            ],
            'visible' => [
              ':input[name=otp_method]' => ['value' => 'sms'],
            ],
          ],
        ];
      $form['otp_email'] = $otp_email + [
          '#states' => [
            'required' => [
              ':input[name=otp_method]' => ['value' => 'email'],
            ],
            'visible' => [
              ':input[name=otp_method]' => ['value' => 'email'],
            ],
          ],
        ];
    }
    else {
      if ($otp_sms) {
        $form_state->set('single_method', 'sms');
        $form['otp_sms']   = $otp_sms;
      }
      elseif ($otp_email) {
        $form_state->set('single_method', 'email');
        $form['otp_email'] = $otp_email;
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
    ];

    /**
     * Attach behaviors
     */
    $form['#attached']['library'][] = 'otp_field_user_auth/otp_login_behavior';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /**
     * determine selected method.
     */
    if ($form_state->get('multiple_methods')) {
      $otp_method = $form_state->getValue('otp_method');
    }
    else {
      $otp_method = $form_state->get('single_method');
    }

    $allow_automatic_registration = $this->config->get('allow_automatic_registration');

    if ($otp_method == 'sms') {
      $phone_field = $this->config->get('phone_number');

      /**
       * Get otp field.
       *
       * Even when the otp_field is not validated, this function is still called.
       * So, we should check whether this field is validated or has errors.
       */
      $otp_sms = $form_state->getValue('otp_sms');
      if (isset($form_state->getErrors()['otp_sms'])) {
        // $otp_sms has errors.
        // stop here.
        // the error will prevent submission.
        return;
      }
      $mobile_number = $otp_sms['otp_id'];

      /**
       * Check if this mobile number is assigned to a user.
       */
      $uids = $this->userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition($phone_field, $mobile_number)
        ->execute();
      if (empty($uids)) {
        // User does not exist with this mobile number.
        // Check if new user registration is allowed in module settings.
        if (!$allow_automatic_registration) {
          $form_state->setErrorByName('otp_sms', $this->t('This mobile number does not belong to an existing account.'));
        }
      }
      elseif (count($uids) > 1) {
        // Multiple users exist with this mobile number
        $form_state->setErrorByName('otp_sms', $this->t('Multiple users exist with this mobile number.'));
        $form_state->setErrorByName('', $this->t('You can @login_by_password or @change_number.', [
          '@login_by_password' => Link::fromTextAndUrl($this->t('login by password'), Url::fromRoute('user.login'))->toString(),
          '@change_number' => Link::fromTextAndUrl($this->t('use another number'), Url::fromRoute('otp_field.user_auth'))->toString(),
        ]));
      }
      else {
        // A Single user exists with this mobile number.
        // Check if this user is allowed to use OTP Login
        $uid = reset($uids);
        /** @var User $user */
        $user = $this->userStorage->load($uid);

        if (!$this->checkUserCanLoginUsingOTP($user)) {
          $form_state->setErrorByName('otp_sms', $this->t('Your account does not have the permission to login by OTP.'));
        }
      }
    }
    elseif ($otp_method == 'email') {
      /**
       * Get otp field.
       *
       * Even when the otp_field is not validated, this function is still called.
       * So, we should check whether this field is validated or has errors.
       */
      $otp_email = $form_state->getValue('otp_email');
      if (isset($form_state->getErrors()['otp_email'])) {
        // $otp_email has errors.
        // stop here.
        // the error will prevent submission.
        return;
      }
      $email_address = $otp_email['otp_id'];

      /**
       * Check if this email address is assigned to a user.
       */
      $users = $this->userStorage->loadByProperties(['mail' => $email_address]);
      if (empty($users)) {
        // User does not exist with this email address.
        // Check if new user registration is allowed in module settings.
        if (!$allow_automatic_registration) {
          $form_state->setErrorByName('otp_email', $this->t('This email address does not belong to an existing account.'));
        }
      }
      elseif (count($users) > 1) {
        // Multiple users exist with this email address
        $form_state->setErrorByName('otp_email', $this->t('Multiple users exist with this email address.'));
        $form_state->setErrorByName('', $this->t('You can @login_by_password or @change_email.', [
          '@login_by_password' => Link::fromTextAndUrl($this->t('login by password'), Url::fromRoute('user.login'))->toString(),
          '@change_email' => Link::fromTextAndUrl($this->t('use another email'), Url::fromRoute('otp_field.user_auth'))->toString(),
        ]));
      }
      else {
        // A Single user exists with this email address.
        // Check if this user is allowed to use OTP Login
        /** @var User $user */
        $user = reset($users);

        if (!$this->checkUserCanLoginUsingOTP($user)) {
          $form_state->setErrorByName('otp_email', $this->t('Your account does not have the permission to login by OTP.'));
        }
      }
    }
    else {
      // NEVER HAPPEN.
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /**
     * determine selected method.
     */
    if ($form_state->get('multiple_methods')) {
      $otp_method = $form_state->getValue('otp_method');
    }
    else {
      $otp_method = $form_state->get('single_method');
    }

    if ($otp_method == 'sms') {
      // Determine the phone_number field.
      $phone_field = $this->config->get('phone_number');

      // Get submitted mobile number
      $mobile_number = $form_state->getValue('otp_sms')['otp_id'];

      /**
       * Once an OTP secret is used, we should expire it.
       * It's necessary for a true One-Time password.
       */
      $this->otpProvider->secretUsed($mobile_number, $form['otp_sms']['#otp_key']);

      /**
       * Check if this mobile number is assigned to a user.
       */
      $uids = $this->userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', '1')
        ->condition($phone_field, $mobile_number)
        ->execute();

      if (empty($uids)) {
        // User does not exist with this mobile number.
        // Register new user.
        $uid = $this->registerNewUserBy('mobile', $mobile_number);
        if ($uid) {
          // new user created. login.
          $this->messenger()->addStatus($this->t('A new account has been created for you.'));
          $this->loginUser($uid);

          // default redirect to user account page (can be overridden by ?destination parameter)
          $form_state->setRedirect('user.page');
        }
        else {
          // could not register new user.
          $this->messenger()->addError($this->t('ERROR: Cannot register new user account.'));
        }
      }
      else {
        // User exists.
        // Login the user
        $uid = reset($uids);
        $this->loginUser($uid);

        // default redirect to user account page (can be overridden by ?destination parameter)
        $form_state->setRedirect('user.page');
      }
    }
    elseif ($otp_method == 'email') {

      // Get submitted email address
      $email_address = $form_state->getValue('otp_email')['otp_id'];

      /**
       * Once an OTP secret is used, we should expire it.
       * It's necessary for a true One-Time password.
       */
      $this->otpProvider->secretUsed($email_address, $form['otp_email']['#otp_key']);

      /**
       * Check if this mobile number is assigned to a user.
       */
      $users = $this->userStorage->loadByProperties(['mail' => $email_address]);

      if (empty($users)) {
        // User does not exist with this email address.
        // Register new user.
        $uid = $this->registerNewUserBy('email', $email_address);
        if ($uid) {
          // new user created. login.
          $this->messenger()->addStatus($this->t('A new account has been created for you.'));
          $this->loginUser($uid);

          // default redirect to user account page (can be overridden by ?destination parameter)
          $form_state->setRedirect('user.page');
        }
        else {
          // could not register new user.
          $this->messenger()->addError($this->t('ERROR: Cannot register new user account.'));
        }
      }
      else {
        // User exists.
        // Login the user
        $user = reset($users);
        $this->loginUser($user->id());

        // default redirect to user account page (can be overridden by ?destination parameter)
        $form_state->setRedirect('user.page');
      }
    }
    else {
      // NEVER HAPPEN.
    }

  }

}
