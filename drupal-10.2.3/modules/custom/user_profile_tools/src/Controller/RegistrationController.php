<?php

namespace Drupal\userProfileTools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Controller for custom registration.
 */
class RegistrationController extends ControllerBase implements ContainerInjectionInterface {

  protected $loggerFactory;

  public function __construct(LoggerChannelFactoryInterface $loggerFactory) {
    $this->loggerFactory = $loggerFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  public function register(Request $request) {
    $content = json_decode($request->getContent(), TRUE);

    try {
      $user = User::create([
        'name' => $content['username'],
        'mail' => $content['email'],
        'pass' => $content['password'],
        'status' => 1, // Active or 0 if email verification is required
        'field_address' => $content['address'],  // Assuming the field machine name is field_address
        'field_telephone' => $content['telephone'],  // Assuming the field machine name is field_telephone
      ]);

      $user->save();

      return new JsonResponse([
        'uid' => $user->id(),
        'message' => 'User registered successfully',
      ]);

    } catch (\Exception $e) {
      $this->loggerFactory->get('custom_registration')->error($e->getMessage());
      return new JsonResponse(['message' => 'An error occurred during registration.'], 500);
    }
  }
}
