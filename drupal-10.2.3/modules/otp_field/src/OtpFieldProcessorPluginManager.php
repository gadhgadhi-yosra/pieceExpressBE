<?php declare(strict_types = 1);

namespace Drupal\otp_field;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\otp_field\Annotation\OtpFieldProcessor;

/**
 * OtpFieldProcessor plugin manager.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 1 2023
 */
final class OtpFieldProcessorPluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/OtpFieldProcessor', $namespaces, $module_handler, OtpFieldProcessorInterface::class, OtpFieldProcessor::class);
    $this->alterInfo('otp_field_processor_info');
    $this->setCacheBackend($cache_backend, 'otp_field_processor_plugins');
  }

}
