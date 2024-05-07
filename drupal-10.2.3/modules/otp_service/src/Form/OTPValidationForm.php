<?php

namespace Drupal\otp_service\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\user\UserStorageInterface;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for OTP Validation.
 */
class OTPValidationForm extends FormBase {

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
   * The user storage.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */

  protected $tempStoreFactory;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Qrcode form constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\user\UserStorageInterface $userStorage
   *   The user storage.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The factory for private temporary storage.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(AccountInterface $currentUser, UserStorageInterface $userStorage, PrivateTempStoreFactory $tempStoreFactory, MessengerInterface $messenger) {
    $this->currentUser = $currentUser;
    $this->userStorage = $userStorage;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('tempstore.private'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_validation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['code'] = [
      '#type' => 'number',
      '#title' => $this->t('Code'),
      '#default_value' => '',
      '#description' => $this->t('Insert here the code from your application (Google Authenticator, Microsoft Authenticator etc...)'),
      '#min' => 000000,
      '#max' => 999999,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Validate Code'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Load user and get key.
    $user = $this->userStorage->load($this->currentUser->id());
    $secret_key = $user->get('otp_secret')->getValue()[0]['value'];
    // Get input form value.
    $code = $form_state->getValue('code');
    $google2fa = new Google2FA();
    if ($google2fa->verifyKey($secret_key, $code)) {
      $node_id = $this->getRequest()->query->get('node_id');
      // Store node_id in private temp store.
      $tempstore = $this->tempStoreFactory->get('otp_service');
      $data = $tempstore->get('allowed_nids');
      if (!$data) {
        $data = [];
      }
      $data[] = $node_id;
      $tempstore->set('allowed_nids', $data);
      $form_state->setRedirect('entity.node.canonical', ['node' => $node_id]);
    }
    else {
      $this->messenger->addError($this->t('It seems the code you provided is not correct. Please try again.'));
    }
  }

}
