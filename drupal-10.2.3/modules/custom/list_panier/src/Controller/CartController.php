<?php

namespace Drupal\list_panier\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\list_panier\Cart\CartManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CartController extends ControllerBase {
  
  protected $cartManager;

  public function __construct(CartManagerInterface $cart_manager) {
    $this->cartManager = $cart_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('list_panier.cart_manager')
    );
  }

  public function addToCart(Request $request) {
    $content = json_decode($request->getContent(), TRUE);

    // Vous devez valider les données ici ...

    $result = $this->cartManager->add($content['product_id'], $content['quantity']);

    if ($result) {
      $response_message = 'Product added to cart successfully.';
    } else {
      $response_message = 'Failed to add product to cart.';
    }

    return new JsonResponse([
      'message' => $response_message,
    ]);
  }

  public function removeFromCart(Request $request) {
    $content = json_decode($request->getContent(), TRUE);

    // Vous devez valider les données ici ...

    $result = $this->cartManager->remove($content['product_id'], $content['quantity']);

    if ($result) {
      $response_message = 'Product removed from cart successfully.';
    } else {
      $response_message = 'Failed to remove product from cart.';
    }

    return new JsonResponse([
      'message' => $response_message,
    ]);
  }
}
