<?php

namespace Drupal\commerce_checkout_otp_login\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\otp_field\OtpProvider;
use Drupal\otp_field_user_auth\Form\SettingsForm;
use Drupal\otp_field_user_auth\OtpUserAuthUtilsTrait;
use Drupal\user\Entity\User;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Commerce Checkout Pane for OTP login.
 *
 * The code in this class is copied and duplicate of
 * @see \Drupal\otp_field_user_auth\Form\UserAuthForm
 * with some crucial modifications.
 *
 * @CommerceCheckoutPane(
 *   id = "otp_login",
 *   label = @Translation("OTP Login"),
 *   default_step = "login",
 * )
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 5 2023
 */
class OTPLogin extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  use OtpUserAuthUtilsTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CheckoutFlowInterface $checkout_flow,
    EntityTypeManagerInterface $entity_type_manager,
    protected ImmutableConfig $config,
    protected UserStorageInterface $userStorage,
    protected RoleStorageInterface $roleStorage,
    protected OtpProvider $otpProvider,
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('config.factory')->get(SettingsForm::CONFIG_NAME),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('otp_field.otp_provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    return \Drupal::currentUser()->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    /**
     * Determine which processor plugins are selected
     */
    // use array_filter to remove unchecked plugins (value=0)
    $enabled_processor_plugins = array_filter($this->config->get('enabled_processor_plugins'));

    if (empty($enabled_processor_plugins)) {
      $msg = $this->t('No OTP plugins are enabled.');

      $this->messenger()->addError($msg);
      $pane_form['error'] = [
        '#markup' => $msg,
      ];

      return $pane_form;
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
        $msg = $this->t('Phone number field is not configured. Please contact site administrator.');

        $this->messenger()->addError($msg);
        $pane_form['error'] = [
          '#markup' => $msg,
        ];

        return $pane_form;
      }

      $otp_sms = [
        '#type' => 'otp_field',
        '#title' => $this->t('Mobile number'),
        '#otp_field_processor' => 'otp_field_sms_processor',
      ];
    }
    if (isset($enabled_processor_plugins['otp_field_email_processor'])) {
      $otp_email = [
        '#type' => 'otp_field',
        '#title' => $this->t('Email address'),
        '#otp_field_processor' => 'otp_field_email_processor',
      ];
    }

    if ($multiple_methods) {
      $form_state->set('multiple_methods', true);
      $pane_form['otp_method'] = [
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
      $pane_form['otp_sms'] = $otp_sms + [
          '#states' => [
            'required' => [
              ':input[name="otp_login[otp_method]"]' => ['value' => 'sms'],
            ],
            'visible' => [
              ':input[name="otp_login[otp_method]"]' => ['value' => 'sms'],
            ],
          ],
        ];
      $pane_form['otp_email'] = $otp_email + [
          '#states' => [
            'required' => [
              ':input[name="otp_login[otp_method]"]' => ['value' => 'email'],
            ],
            'visible' => [
              ':input[name="otp_login[otp_method]"]' => ['value' => 'email'],
            ],
          ],
        ];
    }
    else {
      if ($otp_sms) {
        $form_state->set('single_method', 'sms');
        $pane_form['otp_sms']   = $otp_sms;
      }
      elseif ($otp_email) {
        $form_state->set('single_method', 'email');
        $pane_form['otp_email'] = $otp_email;
      }
    }

    $pane_form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    /**
     * determine selected method.
     */
    if ($form_state->get('multiple_methods')) {
      $otp_method = $form_state->getValue(['otp_login', 'otp_method']);
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
      $otp_sms = $form_state->getValue(['otp_login', 'otp_sms']);
      // Errors are keyed like this! 'otp_login][otp_sms'
      if (isset($form_state->getErrors()['otp_login][otp_sms'])) {
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
          $form_state->setErrorByName('otp_login][otp_sms', $this->t('This mobile number does not belong to an existing account.'));
        }
      }
      elseif (count($uids) > 1) {
        // Multiple users exist with this mobile number
        $form_state->setErrorByName('otp_login][otp_sms', $this->t('Multiple users exist with this mobile number.'));
        // $form_state->setErrorByName('', $this->t('You can @login_by_password or @change_number.', [
        //   '@login_by_password' => Link::fromTextAndUrl($this->t('login by password'), Url::fromRoute('user.login'))->toString(),
        //   '@change_number' => Link::fromTextAndUrl($this->t('use another number'), Url::fromRoute('otp_field.user_auth'))->toString(),
        // ]));
      }
      else {
        // A Single user exists with this mobile number.
        // Check if this user is allowed to use OTP Login
        $uid = reset($uids);
        /** @var User $user */
        $user = $this->userStorage->load($uid);

        if (!$this->checkUserCanLoginUsingOTP($user)) {
          $form_state->setErrorByName('otp_login][otp_sms', $this->t('Your account does not have the permission to login by OTP.'));
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
      $otp_email = $form_state->getValue(['otp_login', 'otp_email']);
      if (isset($form_state->getErrors()['otp_login][otp_email'])) {
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
          $form_state->setErrorByName('otp_login][otp_email', $this->t('This email address does not belong to an existing account.'));
        }
      }
      elseif (count($users) > 1) {
        // Multiple users exist with this email address
        $form_state->setErrorByName('otp_login][otp_email', $this->t('Multiple users exist with this email address.'));
        // $form_state->setErrorByName('', $this->t('You can @login_by_password or @change_email.', [
        //   '@login_by_password' => Link::fromTextAndUrl($this->t('login by password'), Url::fromRoute('user.login'))->toString(),
        //   '@change_email' => Link::fromTextAndUrl($this->t('use another email'), Url::fromRoute('otp_field.user_auth'))->toString(),
        // ]));
      }
      else {
        // A Single user exists with this email address.
        // Check if this user is allowed to use OTP Login
        /** @var User $user */
        $user = reset($users);

        if (!$this->checkUserCanLoginUsingOTP($user)) {
          $form_state->setErrorByName('otp_login][otp_email', $this->t('Your account does not have the permission to login by OTP.'));
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
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    /**
     * determine selected method.
     */
    if ($form_state->get('multiple_methods')) {
      $otp_method = $form_state->getValue(['otp_login', 'otp_method']);
    }
    else {
      $otp_method = $form_state->get('single_method');
    }

    if ($otp_method == 'sms') {
      // Determine the phone_number field.
      $phone_field = $this->config->get('phone_number');

      // Get submitted mobile number
      $mobile_number = $form_state->getValue(['otp_login', 'otp_sms'])['otp_id'];

      /**
       * Once an OTP secret is used, we should expire it.
       * It's necessary for a true One-Time password.
       */
      $this->otpProvider->secretUsed($mobile_number, $pane_form['otp_sms']['#otp_key']);

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
      $email_address = $form_state->getValue(['otp_login', 'otp_email'])['otp_id'];

      /**
       * Once an OTP secret is used, we should expire it.
       * It's necessary for a true One-Time password.
       */
      $this->otpProvider->secretUsed($email_address, $pane_form['otp_email']['#otp_key']);

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
