<?php

namespace Drupal\custom_registration\Controller;

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

    // Basic validation could go here.

    try {
      // Create user entity and save.
      $user = User::create([
        'name' => $content['username'],
        'mail' => $content['email'],
        'pass' => $content['password'],
        'status' => 1, // Or 0 if you want to verify the email address or approve users manually.
      ]);

      // Add other fields like first name, last name if needed.

      $user->save();

      // If you want to issue a token for immediate login, you can use user.auth service here.
      // See: https://www.drupal.org/node/3151009

      return new JsonResponse([
        'uid' => $user->id(),
        'message' => 'User registered successfully',
        // Include additional data as needed.
      ]);

    } catch (\Exception $e) {
      $this->loggerFactory->get('custom_registration')->error($e->getMessage());
      return new JsonResponse(['message' => 'An error occurred during registration.'], 500);
    }
  }
}
