<?php

namespace Drupal\otp_field_user_auth\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\otp_field_user_auth\Form\SettingsForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * Replace or change default user login/register/password pages as configured in settings.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 6 2023
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * config
   */
  protected ImmutableConfig $config;

  /**
   * Constructor
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get(SettingsForm::CONFIG_NAME);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /**
     * replace_default_login
     */
    if ($this->config->get('replace_default_login')) {
      if ($default_login_route = $collection->get('user.login')) {
        $default_login_route->setDefault('_form', 'Drupal\otp_field_user_auth\Form\UserAuthForm');

        if ($otp_login_route = $collection->get('otp_field.user_auth')) {
          $otp_login_route->setRequirement('_access', 'FALSE');
        }
      }
    }

    /**
     * disable_default_register
     */
    if ($this->config->get('disable_default_register')) {
      if ($default_register_route = $collection->get('user.register')) {
        $default_register_route->setRequirement('_access', 'FALSE');
      }
    }

    /**
     * disable_default_password_reset
     */
    if ($this->config->get('disable_default_password_reset')) {
      if ($default_user_pass_route = $collection->get('user.pass')) {
        $default_user_pass_route->setRequirement('_access', 'FALSE');
      }
    }
  }
}
