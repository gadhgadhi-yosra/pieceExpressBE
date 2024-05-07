<?php

namespace Drupal\reset_password_email_otp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\reset_password_email_otp\EmailPassOTPContents;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class for reset password email otp.
 */
class EmailOTPCheck extends FormBase {

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Reset Password Service.
   *
   * @var \Drupal\reset_password_email_otp\EmailPassOTPContents
   */
  protected $emailPassOTPContents;

  /**
   * Class Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config Factory.
   * @param \Drupal\reset_password_email_otp\EmailPassOTPContents $emailPassOTPContents
   *   Config Factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, ConfigFactory $configFactory, EmailPassOTPContents $emailPassOTPContents) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->configFactory = $configFactory;
    $this->emailPassOTPContents = $emailPassOTPContents;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('reset_password_email_otp.email_otp'),

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reset_password_email_otp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if ($form_state->has('step_num') && $form_state->get('step_num') == 2) {
      return $this->fapiotpVerificationPageTwo($form, $form_state);
    }

    if ($form_state->has('step_num') && $form_state->get('step_num') == 3) {
      return $this->fapisetPasswordPageThree($form, $form_state);
    }

    $form_state->set('step_num', 1);

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Identify your account'),
    ];

    $form['email_or_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username or email address'),
      '#description' => $this->t('OTP for reset password will be sent to your registered email address, if account is valid.'),
      '#default_value' => $form_state->getValue('email_or_username', ''),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::fapiStepOneNextSubmit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_values = $form_state->get('page_values');
    $email_or_username = $page_values['email_or_username'];
    $password = $form_state->getValue('confirm_pass');
    $account = user_load_by_mail($email_or_username);
    if (empty($account)) {
      $account = user_load_by_name($email_or_username);
    }
    $user_storage = $this->entityTypeManager->getStorage('user');
    $user = $user_storage->load($account->id());
    $user->setPassword($password);
    $user->save();
  }

  /**
   * Provides custom submission handler for step 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function fapiStepOneNextSubmit(array &$form, FormStateInterface $form_state) {
    $email_or_username = $form_state->getValue('email_or_username');
    $account = user_load_by_mail($email_or_username);
    if (empty($account)) {
      $account = user_load_by_name($email_or_username);
    }
    if (!empty($account)) {
      if ($account->get('status')->value == '1') {
        // Get OTP length config.
        $otp = $this->emailPassOTPContents->getOtpForResetPassword($this->configFactory->getEditable('reset_password_email_otp.settings')
          ->get('reset_password_email_otp_length'));
        // Generate OTP and save in DB for every reset override old one.
        $this->emailPassOTPContents->sendUserResetPasswordOtpWithEmail($account->id(), $otp);
        // Send mail with OTP.
        $this->emailPassOTPContents->resetPasswordEmailOtpSend($account->get('mail')->value, $otp, 'reset-password-email-otp');
      }
    }
    // Make sure the status text is displayed even if no email was sent. This
    // message is deliberately the same as the success message for privacy.
    $this->messenger()
      ->addStatus($this->t('If %identifier is a valid account, an email will be sent with OTP to reset your password.', [
        '%identifier' => $form_state->getValue('email_or_username'),
      ]));
    $form_state
      ->set('page_values', [
        'email_or_username' => $form_state->getValue('email_or_username'),
      ])
      ->set('step_num', 2)
      ->setRebuild(TRUE);
  }

  /**
   * Builds the second step form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function fapiotpVerificationPageTwo(array &$form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Verification'),
    ];

    $form['otp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OTP Validate'),
      '#description' => $this->t('Validate your OTP and reset your password.'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('otp', ''),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::fapiStepTwoBack'],
      '#limit_validation_errors' => [],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::fapiStepTwoNextSubmit'],
      '#validate' => ['::fapiStepTwoFormNextValidate'],
    ];

    return $form;
  }

  /**
   * Provides custom validation handler for page 2.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function fapiStepTwoFormNextValidate(array &$form, FormStateInterface $form_state) {
    $otp = $form_state->getValue('otp');
    $page_values = $form_state->get('page_values');
    $email_or_username = $page_values['email_or_username'];

    $account = user_load_by_mail($email_or_username);
    if (empty($account)) {
      $account = user_load_by_name($email_or_username);
    }
    if (!empty($account)) {
      // Get database connection to get has detail.
      $query = $this->connection->select('reset_password_email_otp_list', 'email_otp')
        ->fields('email_otp', ['resetpass_id', 'uid', 'time', 'OTP', 'count'])
        ->condition('uid', $account->id(), '=')
        ->range(0, 1)
        ->orderBy('time', 'DESC');
      $user_record = $query->execute()->fetchAssoc();
      // Get config limit of OTP wrong attempt.
      $limit_wrong_otp = $this->configFactory->getEditable('reset_password_email_otp.settings')
        ->get('reset_password_email_otp_wrong_attempt');
      if ($otp != $user_record['OTP'] && $user_record != FALSE) {
        if ($user_record['count'] < $limit_wrong_otp) {
          $form_state->setErrorByName('otp', $this->t('The OTP is not valid. Please try again.'));
          $this->connection->update('reset_password_email_otp_list')
            ->fields([
              'count' => $user_record['count'] + 1,
            ])
            ->condition('resetpass_id', $user_record['resetpass_id'], '=')
            ->execute();
        }
        else {
          $form_state->setErrorByName('otp', $this->t('You have tried maximum number of limit, please try after sometime.'));
        }
      }
    }
    else {
      $form_state->setErrorByName('otp', $this->t('The OTP is not valid. Please try again.'));
    }
  }

  /**
   * Provides custom submission handler for step 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function fapiStepTwoNextSubmit(array &$form, FormStateInterface $form_state) {
    $page_values = $form_state->get('page_values');
    $form_state
      ->set('page_values', [
        'email_or_username' => $page_values['email_or_username'],
        'otp' => $form_state->getValue('otp'),
      ])
      ->set('step_num', 3)
      ->setRebuild(TRUE);
  }

  /**
   * Provides custom submission handler for 'Back' button (step 2).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function fapiStepTwoBack(array &$form, FormStateInterface $form_state) {
    $form_state
      ->setValues($form_state->get('page_values'))
      ->set('step_num', 1)
      ->setRebuild(TRUE);
  }

  /**
   * Builds the third step form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function fapisetPasswordPageThree(array &$form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Reset Password'),
    ];

    $form['confirm_pass'] = [
      '#type' => 'password_confirm',
      '#description' => $this->t('Enter the same password in both fields'),
      '#size' => 32,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::fapiStepThreeBack'],
      '#limit_validation_errors' => [],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Provides custom submission handler for 'Back' button (step 2).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function fapiStepThreeBack(array &$form, FormStateInterface $form_state) {
    $form_state
      ->setValues($form_state->get('page_values'))
      ->set('step_num', 2)
      ->setRebuild(TRUE);
  }

}
