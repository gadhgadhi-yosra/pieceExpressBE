<?php

namespace Drupal\otp_field\Controller;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\otp_field\OtpFieldProcessorPluginBase;
use Drupal\otp_field\OtpFieldProcessorPluginManager;
use Drupal\otp_field\OtpProvider;
use Drupal\otp_field\ValidationStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class CheckSecretController extends ControllerBase {

  /**
   * Constructor
   */
  public function __construct(
    protected OtpProvider $otpProvider,
    protected OtpFieldProcessorPluginManager $pluginManager,
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('otp_field.otp_provider'),
      $container->get('plugin.manager.otp_field_processor'),
    );
  }

  /**
   *
   */
  public function checkSecret(Request $request) {
    $identity  = $request->request->get('identity');
    $secret    = $request->request->get('secret');
    $plugin_id = $request->request->get('plugin');
    $otp_key   = $request->request->get('key');

    /**
     * Instantiate OTP Processor plugin
     */
    try {
      /** @var OtpFieldProcessorPluginBase $otp_processor */
      $otp_processor = $this->pluginManager->createInstance($plugin_id);
    }
    catch (PluginException $e) {
      watchdog_exception('otp_field', $e);

      return new JsonResponse([
        'status' => ValidationStatus::ERROR,
        'message' => t('Server-side error.'),
      ]);
    }

    $validation_result = $otp_processor->validateSecret($identity, $secret, $otp_key);

    return new JsonResponse($validation_result);
  }
}
