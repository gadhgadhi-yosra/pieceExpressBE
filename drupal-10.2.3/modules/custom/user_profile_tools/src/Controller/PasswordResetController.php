<?php

namespace Drupal\password_reset\Controller;

use Drupal\Core\Controller\ControllerBase;

class PasswordResetController extends ControllerBase {

  public function resetPassword($uid) {
    // Get user storage object using entityTypeManager() instead of entityManager().
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    // Load user by their user ID.
    $user = $user_storage->load($uid);

    if ($user) {
      // Set the new password.
      $user->setPassword('User123@');

      // Save the user.
      $user->save();

      // Optionally, you may send an email to the user with the new password.
      // This part remains unchanged as it's optional and depends on your workflow.
      \Drupal::service('plugin.manager.mail')->mail(
        'system',
        'mail',
        $user->getEmail(),
        'langcode',
        ['new_password' => 'User123@'], // Ensure this matches the password set above.
        'from_mail',
        TRUE
      );

      // Optionally, you can redirect the user after password reset.
      // This part remains unchanged as well.
      return [
        '#markup' => $this->t('Password reset successfully. A new password has been sent to the user.'),
      ];
    }

    // User not found or password reset failed.
    return [
      '#markup' => $this->t('User not found or password reset failed.'),
    ];
  }
}
