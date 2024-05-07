/**
 * @author "Ahmad Hejazee"
 */
'use strict';
(($, drupalSettings, once) => {

  /**
   * Focus on identity field. (either email field or sms field)
   * Both on a page load, and on method selection change.
   */
  $(() => {
    const focusOnVisibleIdentityField = () => {
      $('#edit-otp-sms-otp-container-otp-id, #edit-otp-email-otp-container-otp-id').each((index, ele) => {
        const $ele = $(ele);
        if ($ele.is(':visible')) {
          $ele.focus();
        }
      });
    };
    focusOnVisibleIdentityField();
    $('#edit-otp-method').on('state:value', focusOnVisibleIdentityField);
  });

  /**
   * This drupal behavior, submits the otp login form as soon as the OTP field is validated.
   */
  Drupal.behaviors.otp_login_behavior = {
    attach: (context, settings) => {
      once('otp_login_behavior', '[data-otp-key]', context).forEach((element) => {

        const $container = $(element);
        $container.on('otp_field:set_status', (e, data) => {
          if (data.new_status === 'verified') {
            const login_form = $('#otp-field-user-auth-form');
            login_form.submit();
          }
        });

      });
    }
  };

})(jQuery, drupalSettings, once);
