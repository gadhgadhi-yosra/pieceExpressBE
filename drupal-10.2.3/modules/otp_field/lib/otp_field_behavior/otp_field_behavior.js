/**
 * @author "Ahmad Hejazee"
 */
'use strict';
(($, drupalSettings, once) => {

  /**
   * Define globals.
   */
  window.OTP_FIELD = {};

  /**
   * Define validation statuses.
   * Must be the same as defined in enum ValidationStatus.
   */
  window.OTP_FIELD.VALIDATION_STATUSES = {
    NONE:      -1,
    SUCCESS:   0,
    CODE_SENT: 1,
    INVALID:   2,
    ERROR:     3,
  };

  /**
   * Append or update or remove a countdown number in an element.
   */
  window.OTP_FIELD.showCountdownNumberOnElement = (element, number) => {
    const pattern = / \(\d+\)/;
    if (number > 0) {
      if (!pattern.test(element.html())) {
        element.html(element.html() + ' (' + number + ')');
      }
      else {
        element.html(element.html().replace(pattern, ' (' + number + ')'));
      }
    }
    else {
      element.html(element.html().replace(pattern, ''));
    }
  };

  /**
   * Set up a countdown on an element.
   */
  window.OTP_FIELD.setUpCountdownOnAnElement = (ele) => {
    // If a countdown already exists, remove it.
    if (ele.attr('data-otp-countdown')) {
      window.OTP_FIELD.removeCountdownFromAnElement(ele);
    }

    let counter = drupalSettings.otp_field.settings.countdown_seconds;

    window.OTP_FIELD.showCountdownNumberOnElement(ele, counter);

    const countdown_id = setInterval(() => {
      counter--;

      window.OTP_FIELD.showCountdownNumberOnElement(ele, counter);
      if (counter <= 0) {
        window.OTP_FIELD.removeCountdownFromAnElement(ele);
      }
    }, 1000);

    ele.attr('data-otp-countdown', countdown_id);
  };

  /**
   * Remove the countdown from an element.
   */
  window.OTP_FIELD.removeCountdownFromAnElement = (ele) => {
    if (ele.attr('data-otp-countdown')) {
      clearInterval(ele.attr('data-otp-countdown'));
      ele.removeAttr('data-otp-countdown');
    }

    window.OTP_FIELD.showCountdownNumberOnElement(ele, 0);
  };

  /**
   * Drupal behavior.
   */
  Drupal.behaviors.otp_field_behavior = {
    attach: (context, settings) => {
      once('otp_field_behavior', '[data-otp-key]', context).forEach((element) => {

        /**
         * Select elements
         */
        const $container      = $(element);
        const $id_field       = $container.find('.js-otp-field-otp-id');
        const $id_wrapper     = $id_field.parent().addClass('otp-id-wrapper');
        const $secret_field   = $container.find('.js-otp-field-otp-secret');
        const $secret_wrapper = $secret_field.parent().addClass('otp-secret-wrapper');

        /**
         * Read otp_key
         */
        const OTP_KEY = $container.attr('data-otp-key');

        /**
         * Read initial status from drupalSettings.
         */
        let validation_status  = drupalSettings.otp_field[OTP_KEY].validation_result.status;
        let validation_message = drupalSettings.otp_field[OTP_KEY].validation_result.message;

        /**
         * Read strings from drupalSettings
         */
        const STRINGS = {
          BTN_SEND_CODE:        drupalSettings.otp_field[OTP_KEY].strings.btn_send_code,
          BTN_RESEND_CODE:      drupalSettings.otp_field[OTP_KEY].strings.btn_resend_code,
          BTN_CHANGE_IDENTITY:  drupalSettings.otp_field[OTP_KEY].strings.btn_change_identity,
          BTN_VERIFY:           drupalSettings.otp_field[OTP_KEY].strings.btn_verify,
          BTN_VERIFY_AGAIN:     drupalSettings.otp_field[OTP_KEY].strings.btn_verify_again,

          MSG_VALIDATED:        drupalSettings.otp_field[OTP_KEY].strings.msg_validated,
          MSG_IDENTITY_MISSING: drupalSettings.otp_field[OTP_KEY].strings.msg_identity_missing,
          MSG_SECRET_MISSING:   drupalSettings.otp_field[OTP_KEY].strings.msg_secret_missing,
        };

        /**
         * Read other settings
         */
        const OTP_PLUGIN_ID = drupalSettings.otp_field[OTP_KEY].otp_plugin;

        /**
         * build action buttons and a message area.
         */
        const $message_area = $('<div class="otp-field-message-area"></div>').appendTo($container);

        const $action_btn_send_code       = $('<a href="#" class="otp-field-action-btn otp-field-action-btn-send-code"></a>')
          .text(STRINGS.BTN_SEND_CODE).appendTo($container);
        const $action_btn_resend_code     = $('<a href="#" class="otp-field-action-btn otp-field-action-btn-resend-code"></a>')
          .text(STRINGS.BTN_RESEND_CODE).appendTo($container);
        const $action_btn_change_identity = $('<a href="#" class="otp-field-action-btn otp-field-action-btn-change-identity"></a>')
          .text(STRINGS.BTN_CHANGE_IDENTITY).appendTo($container);
        const $action_btn_verify          = $('<a href="#" class="otp-field-action-btn otp-field-action-btn-verify"></a>')
          .text(STRINGS.BTN_VERIFY).appendTo($container);
        const $action_btn_verify_again    = $('<a href="#" class="otp-field-action-btn otp-field-action-btn-verify-again"></a>')
          .text(STRINGS.BTN_VERIFY_AGAIN).appendTo($container);

        /**
         * show message function.
         * pass '' to hide message.
         */
        const show_message = (msg, type = 'status') => {
          if (msg === '') {
            $message_area.html('');
          }
          else {
            $message_area.html('<span class="message">' + msg + '</span>');
          }
          if (type === 'status') {
            $message_area.addClass('status').removeClass('error');
          }
          else if (type === 'error') {
            $message_area.addClass('error').removeClass('status');
          }
        };

        /**
         * process server messages, and replace strings if needed.
         */
        const process_server_message = (message) => {
          const strings_pattern = /^@strings:([a-z_]+)$/;
          if (strings_pattern.test(message)) {
            const string_id = strings_pattern.exec(message)[1];
            if (drupalSettings.otp_field[OTP_KEY].strings[string_id] !== undefined) {
              return drupalSettings.otp_field[OTP_KEY].strings[string_id];
            }
          }

          return message;
        };

        /**
         * set-status functions
         */
        const set_status_initial = () => {
          $container.trigger('otp_field:set_status:pre', {new_status: 'initial'});

          validation_status = window.OTP_FIELD.VALIDATION_STATUSES.NONE;
          show_message('');
          $container.attr('otp-validation-status', 'none');

          $container.trigger('otp_field:set_status', {new_status: 'initial'});
        };
        const set_status_verified = () => {
          $container.trigger('otp_field:set_status:pre', {new_status: 'verified'});

          validation_status = window.OTP_FIELD.VALIDATION_STATUSES.SUCCESS;
          show_message('');
          $id_field.attr('readonly', 'readonly');
          $container.attr('otp-validation-status', 'success');

          $container.trigger('otp_field:set_status', {new_status: 'verified'});
        };
        const set_status_code_sent = (message) => {
          $container.trigger('otp_field:set_status:pre', {new_status: 'code_sent'});

          validation_status = window.OTP_FIELD.VALIDATION_STATUSES.CODE_SENT;
          $container.attr('otp-validation-status', 'code_sent');
          show_message(message);

          $container.trigger('otp_field:set_status', {new_status: 'code_sent'});
        };
        const set_status_invalid_secret = (message) => {
          $container.trigger('otp_field:set_status:pre', {new_status: 'invalid_secret'});

          validation_status = window.OTP_FIELD.VALIDATION_STATUSES.INVALID;
          $container.attr('otp-validation-status', 'invalid_secret');
          show_message(message, 'error');

          $container.trigger('otp_field:set_status', {new_status: 'invalid_secret'});
        };
        const set_status_error = (message) => {
          $container.trigger('otp_field:set_status:pre', {new_status: 'error'});

          validation_status = window.OTP_FIELD.VALIDATION_STATUSES.ERROR;
          $container.attr('otp-validation-status', 'error');
          show_message(message, 'error');

          $container.trigger('otp_field:set_status', {new_status: 'error'});
        };

        /**
         * Initial logic (on page load)
         */
        if (validation_status === window.OTP_FIELD.VALIDATION_STATUSES.NONE) {
          set_status_initial();
        }
        else if (validation_status === window.OTP_FIELD.VALIDATION_STATUSES.SUCCESS) {
          set_status_verified();
        }
        else if (validation_status === window.OTP_FIELD.VALIDATION_STATUSES.CODE_SENT) {
          set_status_code_sent(validation_message);
        }
        else if (validation_status === window.OTP_FIELD.VALIDATION_STATUSES.INVALID) {
          set_status_invalid_secret(validation_message);
        }
        else if (validation_status === window.OTP_FIELD.VALIDATION_STATUSES.ERROR) {
          set_status_error(validation_message);
        }

        /**
         * send_code click event.
         */
        $action_btn_send_code.add($action_btn_resend_code).click((e) => {

          /**
           * Countdown: when a countdown is on progress, ignore clicks.
           */
          if ($(e.target).attr('data-otp-countdown')) {
            return false;
          }

          if ($id_field.val()) {
            $.post("/opt_field/check_secret", {
              identity: $id_field.val(),
              secret: '',
              plugin: OTP_PLUGIN_ID,
              key: OTP_KEY,
            })
            .done((result) => {
              if (result.status === window.OTP_FIELD.VALIDATION_STATUSES.CODE_SENT) {
                $container.trigger('otp_field:ajax:pre', {new_status: 'code_sent'});

                set_status_code_sent(process_server_message(result.message));
                $secret_field.val('').focus();

                $container.trigger('otp_field:ajax', {new_status: 'code_sent'});
              }
              else {
                $container.trigger('otp_field:ajax:pre', {new_status: 'error'});

                set_status_error(process_server_message(result.message));
                $secret_field.focus();

                $container.trigger('otp_field:ajax', {new_status: 'error'});
              }
            })
            .fail(() => {
              $container.trigger('otp_field:ajax:pre', {new_status: 'error'});

              set_status_error(Drupal.t('Network error: request failed.'));
              $secret_field.focus();

              $container.trigger('otp_field:ajax', {new_status: 'error'});
            });
          }
          else {
            show_message(STRINGS.MSG_IDENTITY_MISSING, 'error');
            $id_field.focus();
          }

          return false;
        });

        /**
         * Change Identity button click
         */
        $action_btn_change_identity.click(() => {
          set_status_initial();
          // empty the secret field, unmark required. unmark error
          $secret_field.val('').removeAttr('required').removeClass('error');
          // empty the id field, and focus
          $id_field.val('').focus();

          return false;
        });

        /**
         * Verify button click.
         */
        $action_btn_verify.add($action_btn_verify_again).click(() => {
          if ($id_field.val() && $secret_field.val()) {
            $.post("/opt_field/check_secret", {
              identity: $id_field.val(),
              secret: $secret_field.val(),
              plugin: OTP_PLUGIN_ID,
              key: OTP_KEY,
            })
            .done((result) => {
              if (result.status === window.OTP_FIELD.VALIDATION_STATUSES.SUCCESS) {
                $container.trigger('otp_field:ajax:pre', {new_status: 'verified'});

                set_status_verified();

                /**
                 * This event is triggerred ONLY after the ajax responses verified message.
                 * i.e., On set_status_verified(), this event is not triggerred.
                 */
                $container.trigger('otp_field:ajax', {new_status: 'verified'});
              }
              else if (result.status === window.OTP_FIELD.VALIDATION_STATUSES.INVALID) {
                $container.trigger('otp_field:ajax:pre', {new_status: 'invalid'});

                set_status_invalid_secret(process_server_message(result.message));
                $secret_field.val('').focus();

                $container.trigger('otp_field:ajax', {new_status: 'invalid'});
              }
              else {
                $container.trigger('otp_field:ajax:pre', {new_status: 'error'});

                set_status_error(process_server_message(result.message));
                $secret_field.focus();

                $container.trigger('otp_field:ajax', {new_status: 'error'});
              }
            })
            .fail(() => {
              $container.trigger('otp_field:ajax:pre', {new_status: 'error'});

              set_status_error(Drupal.t('Network error: request failed.'));
              $secret_field.focus();

              $container.trigger('otp_field:ajax', {new_status: 'error'});
            });
          }
          else {
            show_message(STRINGS.MSG_SECRET_MISSING, 'error');
            $secret_field.focus();
          }

          return false;
        });

        /**
         * Handle pressing the Enter key on identity and secret fields.
         */
        $secret_field.add($id_field)
          .on('keyup', (e) => {
            /**
             * Listen on keyup event.
             * I chose the keyup event because this event is triggerred only once for each press of key.
             * But the keypress event is triggerred multiple times.
             */
            const keyCode = e.keyCode || e.which;
            if (keyCode === 13) {
              e.preventDefault();

              if ((validation_status === window.OTP_FIELD.VALIDATION_STATUSES.NONE) ||
                  (validation_status === window.OTP_FIELD.VALIDATION_STATUSES.ERROR)) {
                $action_btn_send_code.click();
              }
              else if ((validation_status === window.OTP_FIELD.VALIDATION_STATUSES.CODE_SENT) ||
                       (validation_status === window.OTP_FIELD.VALIDATION_STATUSES.INVALID)) {
                $action_btn_verify.click();
              }

              return false;
            }
          })
          .on('keypress', (e) => {
            /**
             * Discard keypress events with code 13 (enter key)
             * Because, when holding a key, many keypress events are triggerred,
             * and the keypress event can trigger form submit.
             */
            const keyCode = e.keyCode || e.which;
            if (keyCode === 13) {
              e.preventDefault();

              return false;
            }
          });

        /**
         * Display countdown counters.
         */
        $container.on('otp_field:set_status:pre', (e, data) => {
          $action_btn_send_code.add($action_btn_resend_code)
          .each((index, ele) => {
            const $ele = $(ele);
            if (data.new_status === 'code_sent') {
              window.OTP_FIELD.setUpCountdownOnAnElement($ele);
            }
            else {
              window.OTP_FIELD.removeCountdownFromAnElement($ele);
            }
          });
        });

      });
    }
  };

})(jQuery, drupalSettings, once);
