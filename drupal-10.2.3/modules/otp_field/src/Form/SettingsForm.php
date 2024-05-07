<?php

namespace Drupal\otp_field\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * otp_field settings form.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 6 2023
 */
class SettingsForm extends ConfigFormBase {

  public const CONFIG_NAME = 'otp_field.settings';

  /**
   * our editable config.
   */
  protected Config $config;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($config_factory);

    $this->config = $this->config(static::CONFIG_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_field_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['secret_lifetime'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secret lifetime settings'),
    ];
    $form['secret_lifetime']['otp_secret_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('OTP Secret lifetime'),
      '#description' => $this->t('Lifetime of newly generated secrets (per seconds).'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#default_value' => $this->config->get('otp_secret_lifetime'),
      '#size' => 4,
      '#min' => 30,
      '#max' => 60*60,
    ];
    $form['secret_lifetime']['otp_secret_min_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('OTP Secret minimum lifetime'),
      '#description' => $this->t('When an existing secret is returned, its lifetime should not lower than this value. Otherwise, it will be considered expired, and a new secret will be generated. (Per seconds).'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#default_value' => $this->config->get('otp_secret_min_lifetime'),
      '#size' => 4,
      '#min' => 0,
      '#max' => 30*60,
    ];

    $form['clientside_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Client-side settings'),
    ];
    $form['clientside_settings']['countdown_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Countdown timer'),
      '#description' => $this->t('The countdown timer which limits the user from over-clicking the Send Code button.'),
      '#field_suffix' => $this->t('seconds'),
      '#required' => TRUE,
      '#default_value' => $this->config->get('countdown_seconds'),
      '#size' => 3,
      '#min' => 5,
      '#max' => 300,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config->set('otp_secret_lifetime',     (int)$form_state->getValue('otp_secret_lifetime'));
    $this->config->set('otp_secret_min_lifetime', (int)$form_state->getValue('otp_secret_min_lifetime'));
    $this->config->set('countdown_seconds',       (int)$form_state->getValue('countdown_seconds'));

    $this->config->save();

    parent::submitForm($form, $form_state);
  }
}
