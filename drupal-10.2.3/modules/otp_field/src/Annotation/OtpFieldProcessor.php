<?php declare(strict_types = 1);

namespace Drupal\otp_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines otp_field_processor annotation object.
 *
 * @Annotation
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 1 2023
 */
final class OtpFieldProcessor extends Plugin {

  /**
   * The plugin ID.
   */
  public readonly string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public readonly string $title;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public readonly string $description;

}
