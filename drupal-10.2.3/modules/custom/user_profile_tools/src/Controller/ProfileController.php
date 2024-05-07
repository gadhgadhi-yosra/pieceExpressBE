<?php

// namespace Drupal\userProfileTools\Controller;

// use Drupal\Core\Controller\ControllerBase;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Drupal\Core\Session\AccountProxyInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use Drupal\Core\Entity\EntityTypeManagerInterface;
// use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

// class ProfileController extends ControllerBase {

//   protected $currentUser;
//   protected $entityTypeManager;

//   public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
//     $this->currentUser = $current_user;
//     $this->entityTypeManager = $entity_type_manager;
//   }

//   public static function create(ContainerInterface $container) {
//     return new static(
//       $container->get('current_user'),
//       $container->get('entity_type.manager')
//     );
//   }

//   public function updateProfile(Request $request) {
//     $account = $this->currentUser;

//     if (!$account->isAuthenticated()) {
//       throw new AccessDeniedHttpException();
//     }

//     $data = json_decode($request->getContent(), TRUE);
//     if (!$data) {
//       return new JsonResponse(['message' => 'Invalid or no JSON content provided.'], 400);
//     }

//     $user_storage = $this->entityTypeManager->getStorage('user');
//     $user = $user_storage->load($account->id());

//     foreach ($data as $field => $value) {
//       if ($user->hasField($field)) {
//         $user->set($field, $value);
//       }
//     }

//     $user->save();
//     return new JsonResponse(['message' => 'Profile updated successfully.'], 200);
//   }
// }
