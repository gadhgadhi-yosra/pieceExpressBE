<?php

namespace Drupal\otp_service\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserStorageInterface;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Form to generate a secret via QR Code.
 */
class QrCodeForm extends FormBase {

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
   * Qrcode form constructor.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qr_code_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Check if there's already a secret defined for this user.
    $user = $this->userStorage->load($this->currentUser->id());
    $has_secret = !$user->get('otp_secret')->isEmpty();
    // If there is no secret we allow the user to generate it.
    if (!$has_secret) {

      $form['generate_qrcode'] = [
        '#type' => 'submit',
        '#value' => $this->t('Generate QR Code'),
        '#submit' => ['::generateQrcode'],
        '#ajax' => [
          'callback' => '::generateQrcode',
          'event' => 'click',
          'wrapper' => 'edit-qrcode-image',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Generating QR Code...'),
          ],
        ],
      ];

      $form['qrcode_image'] = [
        '#prefix' => '<div id="edit-qrcode-image">',
        '#suffix' => '</div>',
      ];

      return $form;
      // If there's already a secret we inform the user.
    }
    else {

      $form['clear_message'] = [
        '#prefix' => '<div id="edit-clear-message">',
        '#suffix' => '</div>',
        '#markup' => '<p>' . $this->t('It seems you already did the setup for the QR Code') . '</p>',
      ];

      $form['clear_qrcode'] = [
        '#type' => 'submit',
        '#value' => $this->t('Clear Secret'),
        '#submit' => ['::clearSecret'],
        '#ajax' => [
          'callback' => '::clearSecret',
          'event' => 'click',
          'wrapper' => 'edit-clear-message',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Clearing Secret ...'),
          ],
        ],
      ];

      return $form;
    }
  }

  /**
   * Generate a QR Code representating of a secret.
   */
  public function generateQrcode(array &$form, FormStateInterface $form_state) {
    // Generate Secret Key.
    $google2fa = new Google2FA();
    $secret_key = $google2fa->generateSecretKey();
    // Get username of logged in user and set secret value.
    $user = $this->userStorage->load($this->currentUser->id());
    $username = $user->get('name')->value;
    $user->set('otp_secret', $secret_key);
    $user->save();
    // Get hostname.
    $host = $this->getRequest()->getHost();
    // Generate QR Code URL.
    $qrCodeUrl = $google2fa->getQRCodeUrl($host, $username, $secret_key);
    // Logic to generate markup.
    $form['qrcode_image']['#markup'] = '<img alt="QR Code Image" src="https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . $qrCodeUrl . '">';

    return $form['qrcode_image'];
  }

  /**
   * Clear the current secret from the user entity.
   */
  public function clearSecret(array &$form, FormStateInterface $form_state) {
    // Clear secret from the user.
    $user = $this->userStorage->load($this->currentUser->id());
    $user->set('otp_secret', '');
    $user->save();
    $form['clear_message']['#markup'] = '<p> ' . $this->t('Your secret was cleared. Refresh the page to setup the secret again') . '</p>';

    return $form['clear_message'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
