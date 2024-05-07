<?php

namespace Drupal\otp_service\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the OTP Service  settings.
 */
class Settings extends ConfigFormBase {

  /**
   * Provides an interface for an entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * OTP Service Settings constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Provides an interface for an entity type bundle info.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.bundle.info')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_service_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['otp_service.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get current configuration.
    $config = $this->config('otp_service.settings');
    // Populate content type list.
    $contentTypeList = $this->getContentTypes();

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#options' => $contentTypeList,
      '#title' => $this->t('Content Types'),
      '#default_value' => $config->get('content_types'),
      '#description' => $this->t('Select the content types where you want to apply OTP control access.'),
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

    $this->configFactory->getEditable('otp_service.settings')
      ->set('content_types', $form_state->getValue('content_types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get all content type machine names.
   *
   * @return array
   *   A list of content types
   */
  public function getContentTypes() {
    $contentTypesList = [];
    $contentTypes = $this->entityTypeBundleInfo->getBundleInfo('node');
    foreach ($contentTypes as $node_machine_name => $contentType) {
      $contentTypesList[$node_machine_name] = $contentType['label'];
    }
    return $contentTypesList;
  }

}
