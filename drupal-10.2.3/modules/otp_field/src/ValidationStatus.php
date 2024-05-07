<?php

namespace Drupal\otp_field;

/**
 * OTP Validation statues.
 *
 * @author "Ahmad Hejazee" <mngafa@gmail.com>
 * @since Oct 2 2023
 */
enum ValidationStatus: int {
  /**
   * Initial status. No identity is still provided.
   */
  case NONE = -1;

  /**
   * success: secret matches the identity.
   */
  case SUCCESS = 0;

  /**
   * New secret was generated and sent. (When secret is empty)
   */
  case CODE_SENT = 1;

  /**
   * provided secret is invalid.
   */
  case INVALID = 2;

  /**
   * error: cannot process. e.g. when user has been banned.
   */
  case ERROR = 3;
}
