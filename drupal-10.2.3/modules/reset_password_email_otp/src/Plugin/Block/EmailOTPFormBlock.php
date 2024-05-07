<?php

namespace Drupal\reset_password_email_otp\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Block for show Email OTP form.
 *
 * @Block(
 * id = "reset_password_email_otp_form",
 * admin_label = @Translation("Reset Password Email OTP Form"),
 * category = @Translation("Blocks")
 * )
 */
class EmailOTPFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['form'] = $this->formBuilder->getForm('Drupal\reset_password_email_otp\Form\EmailOTPCheck');

    return $build;
  }

  /**
   * Implement max cache zero.
   *
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
