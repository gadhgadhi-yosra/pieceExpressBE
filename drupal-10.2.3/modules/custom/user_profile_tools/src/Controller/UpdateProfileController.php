<?php

namespace Drupal\user_profile_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\user\UserStorageInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Password\PasswordInterface;

class UpdateProfileController extends ControllerBase {

  protected $currentUser;
  protected $entityTypeManager;
  protected $passwordService;

  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, PasswordInterface $password_service) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->passwordService = $password_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('password')
    );
  }

  public function updateProfile(Request $request) {
    if (!$this->currentUser->isAuthenticated()) {
      throw new AccessDeniedHttpException();
    }

    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!$data) {
      return new JsonResponse(['message' => 'Invalid or no JSON content provided.'], 400);
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $user = $user_storage->load($this->currentUser->id());

    if (!$user) {
      return new JsonResponse(['message' => 'User not found.'], 404);
    }

    foreach ($data as $field => $value) {
      if ($user->hasField($field)) {
        $user->set($field, $value);
      }
    }

    $this->handleProfileImage($user, $data); // Check if this method needs error handling
    $user->save();
    return new JsonResponse(['message' => 'Profile updated successfully.'], 200);
  }

  protected function handleProfileImage($user, $data) {
    if (isset($data['profile_image'])) {
      $file = File::load($data['profile_image']);
      if ($file && $file->access('use')) {
        $file->setPermanent();
        $file->save();
        $user->set('user_picture', $file->id());
      } else {
        return new JsonResponse(['message' => 'Invalid file ID or access denied.'], 400);
      }
    }
  }

  public function changePassword(Request $request) {
    if (!$this->currentUser->isAuthenticated()) {
      throw new AccessDeniedHttpException();
    }

    $data = json_decode($request->getContent(), TRUE);
    if (!$data || !isset($data['current_password']) || !isset($data['new_password'])) {
      return new JsonResponse(['message' => 'Current and new password required.'], 400);
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $user = $user_storage->load($this->currentUser->id());

    if (!$user || !$user->isActive()) {
      return new JsonResponse(['message' => 'User not found or inactive.'], 404);
    }

    if (!$this->passwordService->check($data['current_password'], $user->getPassword())) {
      return new JsonResponse(['message' => 'Incorrect current password.'], 403);
    }

    $user->setPassword($data['new_password']);
    $user->save();

    return new JsonResponse(['message' => 'Password updated successfully.'], 200);
  }
}
