//<?php

//namespace Drupal\userProfileTools\Controller;

// use Drupal\Core\Controller\ControllerBase;
// use Drupal\Core\Session\AccountProxyInterface;
// use Drupal\Core\Entity\EntityTypeManagerInterface;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
// use Drupal\user\UserStorageInterface;

// class PasswordController extends ControllerBase {

  // protected $currentUser;
  // protected $entityTypeManager;

  // public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
  //   $this->currentUser = $current_user;
  //   $this->entityTypeManager = $entity_type_manager;
  // }

  // public static function create(ContainerInterface $container) {
  //   return new static(
  //     $container->get('current_user'),
  //     $container->get('entity_type.manager')
  //   );
  // }

  // public function changePassword(Request $request) {
  //   $account = $this->currentUser;

  //   if (!$account->isAuthenticated()) {
  //     throw new AccessDeniedHttpException();
  //   }

  //   $data = json_decode($request->getContent(), TRUE);
  //   if (!$data || !isset($data['current_password']) || !isset($data['new_password'])) {
  //     return new JsonResponse(['message' => 'Current and new password required.'], 400);
  //   }

//     /** @var UserStorageInterface $user_storage */
//     $user_storage = $this->entityTypeManager->getStorage('user');
//     $user = $user_storage->load($account->id());

//     if (!$user || !$user->isActive()) {
//       throw new AccessDeniedHttpException();
//     }

//     // Check if the current password matches.
//     if (!\Drupal::service('password')->check($data['current_password'], $user->getPassword())) {
//       return new JsonResponse(['message' => 'Incorrect current password.'], 403);
//     }

//     // Set the new password.
//     $user->setPassword($data['new_password']);
//     $user->save();

//     return new JsonResponse(['message' => 'Password updated successfully.'], 200);
//   }
// }
