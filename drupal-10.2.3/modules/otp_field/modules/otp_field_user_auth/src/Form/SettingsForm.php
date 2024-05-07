<?php

namespace Drupal\otp_field_user_auth\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\otp_field\OtpFieldProcessorPluginManager;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * otp_field_user_auth settings form.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 2 2023
 */
class SettingsForm extends ConfigFormBase {

  public const CONFIG_NAME = 'otp_field_user_auth.settings';

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected OtpFieldProcessorPluginManager $otpFieldProcessorPluginManager,
    protected RouteBuilderInterface $routeBuilder,
  ) {
    parent::__construct($config_factory);
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.otp_field_processor'),
      $container->get('router.builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_field_user_auth_settings_form';
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
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(static::CONFIG_NAME);

    /**
     * Find telephone fields in the user entity.
     */
    $telephone_fields = [];
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    foreach ($field_definitions as $field_definition) {
      if ($field_definition->getType() == 'telephone') {
        $telephone_fields[$field_definition->getName()] = $field_definition->getLabel();
      }
    }

    /**
     * If the sms framework module is enabled, make sure sms framework does not manage the phone_number field.
     */
    if (\Drupal::moduleHandler()->moduleExists('sms')) {
      if (($our_field = $config->get('phone_number')) && !empty($our_field)) {
        /** @var \Drupal\sms\Provider\PhoneNumberVerificationInterface $phone_number_verifier */
        $phone_number_verifier = \Drupal::service('sms.phone_number.verification');
        $phone_number_settings = $phone_number_verifier->getPhoneNumberSettings('user', 'user');
        if ($phone_number_settings instanceof \Drupal\sms\Entity\PhoneNumberSettingsInterface) {
          $managed_field_name = $phone_number_settings->getFieldName('phone_number');

          if ($our_field == $managed_field_name) {
            $this->messenger()->addWarning($this->t('The @field field is managed by sms framework. Such fields are not supported.', [
              '@field' => $our_field,
            ]));
            if ($phone_number_settings->get('purge_verification_phone_number')) {
              $this->messenger()->addError($this->t('You must disable the "Purge phone numbers" option in <a href="@url">sms framework settings</a>.', [
                '@url' => $phone_number_settings->toUrl()->toString(),
              ]));
            }
          }
        }
      }
    }

    $form['login_behavior'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login behavior'),
    ];

    $form['login_behavior']['allow_automatic_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow automatic registration of new users.'),
      '#description' => $this->t('When this field is checked, if the verified identity does not belong to an existing user account, a new account will be created automatically.'),
      '#default_value' => $config->get('allow_automatic_registration'),
    ];

    /**
     * List available roles.
     */
    // get all roles
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    // filter authenticated, anonymous, and the administrator role
    $roles = array_filter($roles, function(Role $role, string $role_id) {
      return !in_array($role_id, ['authenticated', 'anonymous']) && !$role->isAdmin();
    }, ARRAY_FILTER_USE_BOTH);
    // prepare the array for #options
    array_walk($roles, function(Role& $role) {
      $role = $role->label();
    });

    $form['login_behavior']['new_user_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role assignment'),
      '#description' => $this->t('When a new user is created automatically, this role will be assigned to it.'),
      '#options' => $roles,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $config->get('new_user_role'),
      '#states' => [
        'visible' => [
          ':input[name="allow_automatic_registration"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['login_behavior']['prevent_admin_role_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent all admin users from logging in using OTP.'),
      '#description' => $this->t('Prevents all users with administration role, from logging in using OTP.'),
      '#default_value' => $config->get('prevent_admin_role_login'),
    ];
    $form['login_behavior']['prevent_uid1_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent the admin user from logging in using OTP.'),
      '#description' => $this->t('Prevent the admin user (uid=1) from logging in using OTP.'),
      '#default_value' => $config->get('prevent_uid1_login'),
      '#states' => [
        'disabled' => [
          ':input[name="prevent_admin_role_login"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['replacements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Replacements'),
    ];
    $form['replacements']['replace_default_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace default login forms'),
      '#description' => $this->t('Replace default login page and login block.'),
      '#default_value' => $config->get('replace_default_login'),
    ];
    $form['replacements']['disable_default_register'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable default registration page'),
      '#description' => $this->t('Disable default registration page.'),
      '#default_value' => $config->get('disable_default_register'),
    ];
    $form['replacements']['disable_default_password_reset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable default password reset page'),
      '#description' => $this->t('Disable default password reset page.'),
      '#default_value' => $config->get('disable_default_password_reset'),
    ];

    /**
     * Processor plugins
     *
     * Currently only these plugins are supported:
     *  - otp_field_email_processor
     *  - otp_field_sms_processor
     */
    $available_plugins = $this->otpFieldProcessorPluginManager->getDefinitions();
    $plugin_options = [];
    foreach ($available_plugins as $plugin) {
      $supported_plugins = [
        'otp_field_email_processor',
        'otp_field_sms_processor',
      ];
      if (in_array($plugin['id'], $supported_plugins)) {
        $plugin_options[$plugin['id']] = $plugin['label'] . ' (' . $plugin['provider']  . ')';
      }
    }
    $form['processor_plugin'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processor plugin'),
    ];
    $form['processor_plugin']['enabled_processor_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled processor plugins'),
      '#description' => $this->t(''),
      '#options' => $plugin_options,
      '#default_value' => $config->get('enabled_processor_plugins'),
      '#required' => TRUE,
    ];

    /**
     * When otp_field_sms_processor plugin is selected, user should select phone_number field.
     */
    // Only when otp_field_sms_processor is available.
    if (isset($plugin_options['otp_field_sms_processor'])) {
      $states = [
        'visible' => [
          ':input[name="enabled_processor_plugins[otp_field_sms_processor]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="enabled_processor_plugins[otp_field_sms_processor]"]' => ['checked' => TRUE],
        ],
      ];
      $form['field_mapping'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Field mapping'),
        '#states' => $states,
      ];
      $form['field_mapping']['phone_number'] = [
        '#type' => 'select',
        '#title' => $this->t('Phone number'),
        '#description' => $this->t('Select the field storing phone numbers.') . '<br>' .
          $this->t('WARNING: Telephone fields that are managed by sms framework are not supported. If using such field, make sure to disable the "Purge phone numbers" option.'),
        '#options' => $telephone_fields,
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $config->get('phone_number'),
        '#states' => $states,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // When otp_field_sms_processor plugin is selected, user should select phone_number field.
    $enabled_processor_plugins = $form_state->getValue('enabled_processor_plugins');
    if (!empty($enabled_processor_plugins['otp_field_sms_processor'])) {
      if (empty($form_state->getValue('phone_number'))) {
        $form_state->setErrorByName('phone_number', $this->t('Phone number field is required when SMS method is selected'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);
    $config->set('allow_automatic_registration', (bool)$form_state->getValue('allow_automatic_registration'));
    $config->set('new_user_role',                $form_state->getValue('new_user_role'));

    $config->set('prevent_admin_role_login',     (bool)$form_state->getValue('prevent_admin_role_login'));
    $config->set('prevent_uid1_login',           $form_state->getValue('prevent_uid1_login') ||
                                                 $form_state->getValue('prevent_admin_role_login'));
    /**
     * Replacements section.
     */
    $replace_default_login          = (bool)$form_state->getValue('replace_default_login');
    $disable_default_register       = (bool)$form_state->getValue('disable_default_register');
    $disable_default_password_reset = (bool)$form_state->getValue('disable_default_password_reset');
    // Only if any of these settings is changed.
    if (
      ($config->get('replace_default_login')          != $replace_default_login) ||
      ($config->get('disable_default_register')       != $disable_default_register) ||
      ($config->get('disable_default_password_reset') != $disable_default_password_reset)
    ) {
      // save new settings
      $config->set('replace_default_login',          $replace_default_login);
      $config->set('disable_default_register',       $disable_default_register);
      $config->set('disable_default_password_reset', $disable_default_password_reset);

      /**
       * save config before router rebuild. because it will read our config.
       * @see \Drupal\otp_field_user_auth\Routing\RouteSubscriber::alterRoutes()
       */
      $config->save();

      // rebuild router cache.
      $this->routeBuilder->rebuild();
    }

    /**
     * Processor plugins
     */
    $enabled_processor_plugins = $form_state->getValue('enabled_processor_plugins');
    $config->set('enabled_processor_plugins', $enabled_processor_plugins);
    // set phone_number only when otp_field_sms_processor is selected. otherwise unset it.
    if (empty($enabled_processor_plugins['otp_field_sms_processor'])) {
      $config->set('phone_number', '');
    }
    else {
      $config->set('phone_number', $form_state->getValue('phone_number'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }
}
